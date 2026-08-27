<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\WorkSchedule;
use App\Models\ScheduleException;
use App\Models\LandingSetting;
use App\Models\ShiftTemplate;
use App\Models\Room;
use App\Models\Payment;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Traits\ScheduleTrait;

class AdminController extends Controller
{
    use ScheduleTrait;

    /* ─── HELPERS ─── */

    private function logActivity(string $action, $entity = null, ?string $details = null): void
    {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity ? $entity->id : null,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }

    private function authorizeLandingEditor(): void
    {
        $user = auth()->user();
        if ($user->roles->contains('name', 'admin')) return;
        if ($user->roles->contains('name', 'receptionist') && $user->can_edit_landing) return;
        abort(403, 'Unauthorized');
    }

    /* ─── DASHBOARD ─── */

    public function dashboard()
    {
        $today = Carbon::today();

        $todayRevenue = Payment::whereDate('paid_at', $today)
            ->whereIn('type', ['completion', 'additional', 'full'])
            ->sum('amount');

        $todayAppointments = Appointment::whereDate('appointment_date', $today)->count();
        $completedToday = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'completed')
            ->count();

        $todayDow = $today->dayOfWeek;
        $staffOnDuty = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->whereHas('workSchedules', function ($q) use ($todayDow) {
                $q->where('day_of_week', $todayDow)->where('is_day_off', false);
            })->count();

        $depositsHeld = Appointment::where('status', 'pending')
            ->where('deposit_required', '>', 0)
            ->sum('deposit_required');

        $todayNoShows = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'cancelled')
            ->where(function ($q) {
                $q->where('cancellation_reason', 'customer_no_show')
                  ->orWhereNull('cancellation_reason');
            })->count();

        return view('admin-dashboard', compact(
            'todayRevenue', 'todayAppointments', 'completedToday',
            'staffOnDuty', 'depositsHeld', 'todayNoShows'
        ));
    }

    /* ─── USERS ─── */

    public function index(Request $request)
    {
        $query = User::with('roles')
            ->when($request->filled('search'), function($q) use ($request) {
                $search = $request->search;
                $q->where(function($sub) use ($search) {
                    $sub->where('username', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function($q) use ($request) {
                $q->whereHas('roles', fn($r) => $r->where('name', $request->role));
            })
            ->latest();

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::all();

        return view('admin.users', compact('users', 'roles'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);
        $user->update(['first_name' => $request->first_name, 'last_name' => $request->last_name]);
        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username', 'regex:/^\S+$/u'],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => [
                'required', 'string', 'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'role' => 'required|in:admin,receptionist,staff,customer',
        ], [
            'username.regex' => 'The username must not contain spaces.',
            'password.mixed' => 'The password must contain both uppercase and lowercase letters.',
            'password.numbers' => 'The password must contain at least one number.',
            'password.symbols' => 'The password must contain at least one symbol (e.g. !@#$%).',
        ]);

        $role = Role::where('name', $validated['role'])->first();
        if (!$role) {
            return redirect()->back()->withInput()
                ->with('error', 'Role ['.$validated['role'].'] does not exist. Run: php artisan db:seed --class=RoleSeeder');
        }

        try {
            $user = DB::transaction(function () use ($validated, $role) {
                $user = User::create([
                    'username' => $validated['username'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'password' => Hash::make($validated['password']),
                ]);
                $user->roles()->attach($role->id);

                if ($role->name === 'customer') {
                    \App\Models\Customer::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'phone_number' => 'N/A',
                            'customer_type' => 'regular',
                        ]
                    );
                }
                return $user;
            });

            $this->logActivity('user_created', $user, 'Created as '.$role->name);
            return redirect()->route('admin.users.index')
                ->with('success', 'User '.$user->username.' created as '.$role->name.'.');
        } catch (\Exception $e) {
            Log::error('User create failed: '.$e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Failed to create user. Please try again.');
        }
    }

    public function update(Request $request, User $user)
    {
        if (!Hash::check($request->input('admin_password'), auth()->user()->password)) {
            return redirect()->back()->withInput()->with('edit_user_id', $user->id)
                ->with('error', 'Your admin password is incorrect. Update cancelled.');
        }

        $rules = [
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$user->id, 'regex:/^\S+$/u'],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role' => 'required|in:admin,receptionist,staff,customer',
        ];
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:6|confirmed';
        }

        $validated = $request->validate($rules, ['username.regex' => 'The username must not contain spaces.']);
        $role = Role::where('name', $validated['role'])->first();
        if (!$role) {
            return redirect()->back()->withInput()->with('edit_user_id', $user->id)
                ->with('error', 'Role ['.$validated['role'].'] not found.');
        }

        try {
            DB::transaction(function () use ($validated, $user, $role, $request) {
                $updateData = [
                    'username' => $validated['username'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                ];
                if ($request->filled('password')) {
                    $updateData['password'] = Hash::make($validated['password']);
                    $updateData['password_changed_at'] = now();
                    $user->increment('session_version');
                }
                $user->update($updateData);
                $user->roles()->sync([$role->id]);

                if ($role->name === 'customer') {
                    \App\Models\Customer::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'phone_number' => 'N/A',
                            'customer_type' => 'regular',
                        ]
                    );
                }
            });

            $this->logActivity('user_updated', $user, 'Role set to '.$role->name);
            return redirect()->route('admin.users.index')
                ->with('success', 'User '.$user->username.' updated to '.$role->name.'.');
        } catch (\Exception $e) {
            Log::error('User update failed: '.$e->getMessage());
            return redirect()->back()->withInput()->with('edit_user_id', $user->id)
                ->with('error', 'Update failed: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }
        if (!Hash::check($request->input('admin_password'), auth()->user()->password)) {
            return redirect()->back()->with('error', 'Your admin password is incorrect. Delete cancelled.');
        }

        $blockers = [];
        $staffAppts = Appointment::where('user_id', $user->id)->count();
        if ($staffAppts > 0) $blockers[] = $staffAppts.' appointment(s) as assigned staff';
        $createdAppts = Appointment::where('created_by', $user->id)->count();
        if ($createdAppts > 0) $blockers[] = $createdAppts.' appointment(s) created by this user';
        if ($user->customerProfile) {
            $custAppts = Appointment::where('customer_id', $user->customerProfile->id)->count();
            if ($custAppts > 0) $blockers[] = $custAppts.' appointment(s) booked as customer';
        }

        if (!empty($blockers)) {
            return redirect()->back()->with('error',
                'Cannot delete user '.$user->username.' — they have '.implode(', ', $blockers).'. Deactivate the account instead to preserve history.'
            );
        }

        try {
            $username = $user->username;
            DB::transaction(function () use ($user) {
                if ($user->customerProfile) $user->customerProfile->delete();
                $user->roles()->detach();
                $user->delete();
            });
            $this->logActivity('user_deleted', null, 'Permanently deleted user '.$username.' (ID: '.$user->id.')');
            return redirect()->route('admin.users.index')
                ->with('success', 'User '.$username.' permanently deleted.');
        } catch (\Exception $e) {
            Log::error('User delete failed: '.$e->getMessage());
            return redirect()->back()->with('error', 'Delete failed: '.$e->getMessage());
        }
    }

    public function deactivate(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }
        if (!Hash::check($request->input('admin_password'), auth()->user()->password)) {
            return redirect()->back()->with('error', 'Your admin password is incorrect. Action cancelled.');
        }

        $user->update(['is_active' => false]);
        $this->logActivity('user_deactivated', $user, 'Deactivated by admin');

        \App\Http\Controllers\NotificationController::sendTo(
            $user,
            'Account Deactivated',
            'Your account has been deactivated by an administrator. Contact support if you believe this is an error.',
            'account', 'danger'
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User '.$user->username.' deactivated. Their history and role are preserved.');
    }

    public function reactivate(Request $request, User $user)
    {
        if (!Hash::check($request->input('admin_password'), auth()->user()->password)) {
            return redirect()->back()->with('error', 'Your admin password is incorrect. Action cancelled.');
        }
        $user->update(['is_active' => true]);
        $this->logActivity('user_reactivated', $user, 'Reactivated by admin');
        return redirect()->route('admin.users.index')
            ->with('success', 'User '.$user->username.' reactivated successfully.');
    }

    /* ─── SERVICES ─── */

    public function servicesIndex()
    {
        $services = Service::with(['category', 'roomCategory'])->orderBy('category_id')->orderBy('name')->get();
        $categories = ServiceCategory::withCount('services')->get();
        $singleServices = Service::where('is_package', false)->where('is_active', true)->get();
        return view('admin.services.index', compact('services', 'categories', 'singleServices'));
    }

    public function servicesStore(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'description' => 'nullable|string',
            'landing_description' => 'nullable|string|max:500',
            'image' => 'nullable|image|max:2048',
            'is_package' => 'boolean',
            'included_services' => 'nullable|array',
            'included_services.*' => 'exists:services,id',
            'is_active' => 'boolean',
            'show_on_landing' => 'boolean',
            'deposit_percentage_min' => 'nullable|integer|min:0|max:100',
            'deposit_percentage_max' => 'nullable|integer|min:0|max:100|gte:deposit_percentage_min',
            'requires_room' => 'boolean',
            'room_category_id' => 'nullable|exists:service_categories,id',
        ]);

        $validated['is_package'] = $request->boolean('is_package');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['show_on_landing'] = $request->boolean('show_on_landing', true);
        $validated['requires_room'] = $request->boolean('requires_room', true);

        if (!$validated['requires_room']) $validated['room_category_id'] = null;
        if (!$validated['is_package']) $validated['included_services'] = null;
        if ($request->hasFile('image')) {
            $validated['image'] = '/storage/' . $request->file('image')->store('services', 'public');
        }

        Service::create($validated);
        return redirect()->route('admin.services.index')->with('success', 'Service created successfully.');
    }

    public function servicesUpdate(Request $request, Service $service)
    {
        if ($request->has('is_active') && count($request->all()) <= 3) {
            $service->update(['is_active' => $request->boolean('is_active')]);
            return back()->with('success', 'Status updated successfully.');
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'description' => 'nullable|string',
            'is_package' => 'boolean',
            'included_services' => 'nullable|array',
            'included_services.*' => 'exists:services,id',
            'is_active' => 'boolean',
            'landing_description' => 'nullable|string|max:500',
            'image' => 'nullable|image|max:2048',
            'show_on_landing' => 'boolean',
            'deposit_percentage_min' => 'nullable|integer|min:0|max:100',
            'deposit_percentage_max' => 'nullable|integer|min:0|max:100|gte:deposit_percentage_min',
            'requires_room' => 'boolean',
            'room_category_id' => 'nullable|exists:service_categories,id',
        ]);

        $validated['is_package'] = $request->boolean('is_package');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_on_landing'] = $request->boolean('show_on_landing', true);
        $validated['requires_room'] = $request->boolean('requires_room', true);

        if (!$validated['requires_room']) $validated['room_category_id'] = null;
        if (!$validated['is_package']) $validated['included_services'] = null;
        if (empty($validated['discount_price'])) $validated['discount_price'] = null;
        unset($validated['discount_percent']);

        // Consolidated image handling
        if ($request->boolean('remove_image') && $service->image) {
            $this->deleteServiceImage($service->image);
            $validated['image'] = null;
        } elseif ($request->hasFile('image')) {
            if ($service->image) $this->deleteServiceImage($service->image);
            $validated['image'] = '/storage/' . $request->file('image')->store('services', 'public');
        }

        $service->update($validated);
        return redirect()->route('admin.services.index')->with('success', 'Service updated successfully.');
    }

    private function deleteServiceImage(?string $imagePath): void
    {
        if (!$imagePath) return;
        $path = str_replace(['/storage/', asset('storage/')], '', $imagePath);
        Storage::disk('public')->delete($path);
    }

    public function servicesDestroy(Service $service)
    {
        $appointmentCount = DB::table('appointment_services')->where('service_id', $service->id)->count();
        if ($appointmentCount > 0) {
            return redirect()->route('admin.services.index')
                ->with('error', 'Cannot delete service '.$service->name.' — it has '.$appointmentCount.' booking record(s). Deactivate it instead.');
        }

        $staffCount = DB::table('service_staff')->where('service_id', $service->id)->count();
        if ($staffCount > 0) {
            return redirect()->route('admin.services.index')
                ->with('error', 'Cannot delete service '.$service->name.' — it is assigned to '.$staffCount.' staff member(s). Remove assignments first.');
        }

        $packageCount = Service::whereRaw(
            'JSON_CONTAINS(included_services, ?) OR JSON_CONTAINS(included_services, ?)',
            [json_encode((int) $service->id), json_encode((string) $service->id)]
        )->count();
        if ($packageCount > 0) {
            return redirect()->route('admin.services.index')
                ->with('error', 'Cannot delete service '.$service->name.' — it is included in '.$packageCount.' package(s). Remove from packages first.');
        }

        if ($service->image) $this->deleteServiceImage($service->image);
        $service->delete();
        return redirect()->route('admin.services.index')
            ->with('success', 'Service '.$service->name.' deleted permanently.');
    }

    /* ─── CATEGORIES ─── */

    public function categoriesIndex()
    {
        $categories = ServiceCategory::withCount('services')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function categoriesStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'deposit_percentage' => 'nullable|integer|min:0|max:100',
        ]);
        ServiceCategory::create([
            'name' => $request->name,
            'color' => $request->color ?? '#7c9684',
            'deposit_percentage' => $request->deposit_percentage,
        ]);
        return redirect()->back()->with('success', 'Category added.');
    }

    public function categoriesUpdate(Request $request, ServiceCategory $category)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|nullable|string|max:7',
            'is_active' => 'boolean',
            'deposit_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        if ($request->has('name')) $category->name = $request->name;
        if ($request->has('color')) $category->color = $request->color;
        if ($request->has('deposit_percentage')) $category->deposit_percentage = $request->deposit_percentage;
        if ($request->has('is_active')) $category->is_active = $request->boolean('is_active');
        $category->save();

        $message = $request->has('is_active')
            ? ($category->is_active ? 'Category activated.' : 'Category deactivated.')
            : 'Category updated.';
        return redirect()->back()->with('success', $message);
    }

    public function categoriesDestroy($id)
    {
        $category = ServiceCategory::findOrFail($id);
        if ($category->services()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete category ['.$category->name.']. Please move or delete the services inside it first.');
        }
        $category->delete();
        return redirect()->back()->with('success', 'Category deleted successfully!');
    }

    /* ─── SHIFT TEMPLATES ─── */

    public function templatesIndex()
    {
        $templates = ShiftTemplate::with('creator')->latest()->get();
        return view('admin.shift-templates', compact('templates'));
    }

    public function templatesStore(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'pattern' => 'required|array']);
        ShiftTemplate::create(['name' => $request->name, 'created_by' => auth()->id(), 'pattern' => $request->pattern]);
        return back()->with('success', 'Template created');
    }

    public function templatesUpdate(Request $request, ShiftTemplate $template)
    {
        $request->validate(['name' => 'required|string|max:255', 'pattern' => 'required|array', 'is_active' => 'boolean']);
        $template->update([
            'name' => $request->name,
            'pattern' => $request->pattern,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return back()->with('success', 'Template updated');
    }

    public function templatesDestroy(ShiftTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template deleted');
    }

    /* ─── LANDING ─── */

    public function landingEditor()
    {
        $this->authorizeLandingEditor();
        $heroSettings = [
            'hero_title' => LandingSetting::where('key', 'hero_title')->first()?->value ?? 'Spa Alexandria',
            'hero_subtitle' => LandingSetting::where('key', 'hero_subtitle')->first()?->value ?? '',
            'hero_image' => LandingSetting::where('key', 'hero_image')->first()?->value ?? null,
        ];
        $categories = ServiceCategory::with(['services' => fn($q) => $q->orderBy('name')])->orderBy('name')->get();
        $receptionists = User::whereHas('roles', fn($q) => $q->where('name', 'receptionist'))
            ->get(['id', 'first_name', 'last_name', 'can_edit_landing']);
        return view('shared.landing-editor', compact('heroSettings', 'categories', 'receptionists'));
    }

    public function landingUpdate(Request $request)
    {
        $this->authorizeLandingEditor();
        $validated = $request->validate([
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            'hero_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('hero_image')) {
            $old = LandingSetting::where('key', 'hero_image')->first();
            if ($old && $old->value) {
                $oldPath = str_replace(asset('storage/'), '', $old->value);
                $oldPath = str_replace('/storage/', '', $oldPath);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('hero_image')->store('landing', 'public');
            LandingSetting::updateOrCreate(['key' => 'hero_image'], ['value' => asset('storage/' . $path)]);
        }

        LandingSetting::updateOrCreate(['key' => 'hero_title'], ['value' => $validated['hero_title']]);
        LandingSetting::updateOrCreate(['key' => 'hero_subtitle'], ['value' => $validated['hero_subtitle'] ?? '']);

        if ($request->has('categories')) {
            foreach ($request->categories as $catId => $data) {
                ServiceCategory::where('id', $catId)->update(['show_on_landing' => !empty($data['show'])]);
            }
        }

        if ($request->has('services')) {
            foreach ($request->services as $serviceId => $data) {
                $service = Service::find($serviceId);
                if (!$service) continue;
                $update = [
                    'show_on_landing' => !empty($data['show']),
                    'landing_description' => $data['landing_description'] ?? null,
                ];
                if (!empty($data['remove_image']) && $service->image) {
                    $this->deleteServiceImage($service->image);
                    $update['image'] = null;
                }
                $service->update($update);
            }
        }

        if ($request->hasFile('service_images')) {
            foreach ($request->file('service_images') as $serviceId => $file) {
                if (!$file || !$file->isValid()) continue;
                $service = Service::find($serviceId);
                if (!$service) continue;
                if ($service->image) $this->deleteServiceImage($service->image);
                $path = $file->store('services', 'public');
                $service->update(['image' => asset('storage/' . $path)]);
            }
        }

        return back()->with('success', 'Landing page updated successfully.');
    }

    public function toggleLandingPermission(User $user)
    {
        if (!$user->roles()->where('name', 'receptionist')->exists()) {
            return back()->with('error', 'User is not a receptionist');
        }
        $user->update(['can_edit_landing' => !$user->can_edit_landing]);
        $status = $user->can_edit_landing ? 'CAN EDIT' : 'VIEW ONLY';
        return back()->with('success', $user->first_name . ' ' . $user->last_name . ' is now ' . $status);
    }

    /* ─── ROOMS ─── */

    public function roomsIndex()
    {
        $rooms = Room::with('category')->orderBy('name')->get();
        $categories = ServiceCategory::where('is_active', true)->orderBy('name')->get();
        return view('admin.rooms', compact('rooms', 'categories'));
    }

    public function roomsStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:rooms',
            'category_id' => 'nullable|exists:service_categories,id',
            'status' => 'required|in:available,occupied,maintenance',
            'notes' => 'nullable|string|max:1000',
        ]);
        Room::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'status' => $request->status,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return redirect()->route('admin.rooms.index')->with('success', 'Room created successfully.');
    }

    public function roomsUpdate(Request $request, Room $room)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:rooms,name,' . $room->id,
            'category_id' => 'nullable|exists:service_categories,id',
            'status' => 'required|in:available,occupied,maintenance',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);
        $room->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'status' => $request->status,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return redirect()->route('admin.rooms.index')->with('success', 'Room updated successfully.');
    }

    public function roomsDestroy(Room $room)
    {
        if ($room->appointments()->exists()) {
            return back()->with('error', 'Cannot delete room with existing appointments.');
        }
        $room->delete();
        return redirect()->route('admin.rooms.index')->with('success', 'Room deleted.');
    }

    /* ─── APPOINTMENTS ─── */

    public function appointments(Request $request)
    {
        $today = Carbon::today();

        $baseQuery = Appointment::with(['customer', 'services', 'staff', 'room', 'payments'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function($q) use ($request) {
                $search = $request->search;
                $q->whereHas('customer', fn($sq) =>
                    $sq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('phone_number', 'like', "%{$search}%")
                );
            })
            ->when($request->filled('staff_id'), fn($q) => $q->where('user_id', $request->staff_id))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('appointment_date', '>=', Carbon::parse($request->date_from)))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('appointment_date', '<=', Carbon::parse($request->date_to)));

        $appointments = (clone $baseQuery)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $baseQuery)->where('status', 'confirmed')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'no_show' => (clone $baseQuery)->where('status', 'cancelled')->where('cancellation_reason', 'customer_no_show')->count(),
            'today' => Appointment::whereDate('appointment_date', $today)->count(),
            'today_revenue' => Payment::whereDate('paid_at', $today)
                ->whereIn('type', ['completion', 'additional', 'full'])
                ->sum('amount'),
        ];

        $staffList = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('admin.appointments', compact('appointments', 'stats', 'staffList'));
    }
}