<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReceptionistController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ScheduleController;


// ==================== PUBLIC ROUTES (NO LOGIN REQUIRED) ====================
Route::middleware('web')->group(function () {
    Route::get('/', [BookingController::class, 'landing'])->name('landing');
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('customer.register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/terms', fn() => view('terms'))->name('terms');
    Route::get('/privacy', fn() => view('privacy'))->name('privacy');

    // Booking (guests can book without logging in)
    Route::get('/book', [BookingController::class, 'wizard'])->name('booking.wizard');
    Route::get('/api/booking/slots', [BookingController::class, 'getSlots'])->name('booking.slots');
    Route::get('/api/occupied-slots', [BookingController::class, 'occupiedSlots'])->name('api.occupied-slots');
    Route::post('/book', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/book/confirmation/{appointment}', [BookingController::class, 'confirmation'])->name('booking.confirmation');

    // Staff gaps for continuous scheduling
    Route::get('/api/booking/staff-gaps', [BookingController::class, 'staffGaps'])->name('api.staff-gaps');

    // Next available slot
    Route::get('/api/booking/next-slot', [BookingController::class, 'nextAvailableSlot'])->name('api.next-slot');
});

// ==================== AUTHENTICATED USERS (ALL ROLES) ====================
Route::middleware(['auth'])->group(function () {
    Route::put('/profile', [ReceptionistController::class, 'updateProfile'])->name('profile.update');
});

// ==================== CUSTOMER ONLY ====================
Route::middleware(['auth', 'role:customer'])->prefix('customer')->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'index'])->name('customer-dashboard');
    Route::put('/medical-notes', [CustomerController::class, 'updateMedicalNotes'])->name('customer.medical-notes.update');
    Route::put('/profile', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
    Route::get('/profile', [CustomerController::class, 'profile'])->name('customer.profile');
});

// ==================== STAFF ====================
Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffController::class, 'index'])->name('dashboard');
    Route::get('/appointments', [StaffController::class, 'myAppointments'])->name('appointments');
    Route::get('/schedule', [StaffController::class, 'mySchedule'])->name('schedule');
    Route::post('/schedule/update', [StaffController::class, 'updateMySchedule'])->name('schedule.update');
});

// ==================== RECEPTIONIST ====================
Route::middleware(['auth', 'role:receptionist'])->get('/receptionist-dashboard', function () {
    return redirect('/receptionist/dashboard');
});

// ─── Receptionist View Routes (all receptionists can view) ───
Route::middleware(['auth', 'role:receptionist'])->prefix('receptionist')->group(function () {
    Route::get('/dashboard', [ReceptionistController::class, 'desk'])->name('receptionist.dashboard');
    Route::get('/booking/{appointment}', [ReceptionistController::class, 'booking'])->name('receptionist.booking');
    Route::post('/booking/{appointment}/confirm', [ReceptionistController::class, 'confirm'])->name('receptionist.confirm');
    Route::post('/booking/{appointment}/cancel', [ReceptionistController::class, 'cancel'])->name('receptionist.cancel');
    Route::post('/appointments/{appointment}/complete', [ReceptionistController::class, 'complete'])->name('receptionist.complete');
    Route::get('/pending', [ReceptionistController::class, 'pending'])->name('receptionist.pending');
    Route::get('/active', [ReceptionistController::class, 'active'])->name('receptionist.active');
    Route::get('/sales', [SalesReportController::class, 'index'])->name('receptionist.sales');
    Route::get('/sales/tx-log', [SalesReportController::class, 'transactionLogFragment'])->name('receptionist.sales.tx-log');
    Route::get('/sales/daily-report-pdf', [SalesReportController::class, 'dailyReportPdf'])->name('receptionist.sales.daily-report-pdf');
    Route::get('/sales/business-report-pdf', [SalesReportController::class, 'businessReportPdf'])->name('receptionist.sales.business-report-pdf');
    Route::get('/booking/{appointment}/rooms', [ReceptionistController::class, 'availableRooms'])->name('receptionist.rooms.available');
    Route::get('/quick-book', [BookingController::class, 'quickBook'])->name('receptionist.quick-book');
    Route::get('/api/booking/next-slots', [BookingController::class, 'nextSlots'])->name('api.next-slots');
    Route::post('/sales/ai-chat', [SalesReportController::class, 'aiChat'])->name('receptionist.sales.ai-chat');
    Route::post('/appointments/{appointmentId}/reassign', [ReceptionistController::class, 'reassignStaff'])->name('receptionist.reassign')->whereNumber('appointmentId');
    Route::post('/appointments/{appointmentId}/reschedule', [ReceptionistController::class, 'reschedule'])->name('receptionist.reschedule')->whereNumber('appointmentId');
    Route::get('/api/customers/lookup', [BookingController::class, 'customerLookup'])->name('api.customers.lookup');

    // Mid-session extra services
    Route::post('/appointments/{appointment}/add-extra', [ReceptionistController::class, 'addExtraService'])->name('receptionist.add-extra');
    // No-show deposit handling
    Route::post('/appointments/{appointment}/no-show', [ReceptionistController::class, 'handleNoShow'])->name('receptionist.no-show');

    // ─── SCHEDULE VIEW ROUTES (all receptionists can view) ───
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('receptionist.schedules');
    Route::get('/shift-templates', [ScheduleController::class, 'templates'])->name('receptionist.shift-templates.index');
    Route::get('/api/staff/{staff}/schedule', [ScheduleController::class, 'staffScheduleApi'])->name('receptionist.api.staff.schedule');
});

// ─── Receptionist Schedule Edit Routes (requires can_manage_schedules) ───
Route::middleware(['auth', 'role:receptionist', 'can_manage_schedules'])->prefix('receptionist')->group(function () {
    Route::post('/schedules/bulk-update', [ScheduleController::class, 'bulkUpdate'])->name('receptionist.schedules.bulk-update');
    // FIX: Route order matters! Bulk route must come BEFORE the parameterized route
    Route::post('/schedules/template/bulk', [ScheduleController::class, 'applyTemplateBulk'])->name('receptionist.schedules.template.bulk');
    // FIX: Changed from /schedules/template/{user} to /schedules/template/apply/{user}
    // to avoid conflict with /schedules/template/bulk
    Route::post('/schedules/template/apply/{user}', [ScheduleController::class, 'applyTemplate'])->name('receptionist.schedules.template');
    Route::post('/schedules/block', [ScheduleController::class, 'quickBlock'])->name('receptionist.schedules.block');
    Route::post('/schedules/move-shift', [ScheduleController::class, 'moveShift'])->name('receptionist.schedules.move-shift');
    Route::post('/schedule-template/update', [ScheduleController::class, 'updateTemplate'])->name('receptionist.schedule-template.update');
    Route::post('/schedule-exception/store', [ScheduleController::class, 'storeException'])->name('receptionist.schedule-exception.store');

    // Reusable ShiftTemplate CRUD
    Route::post('/shift-templates', [ScheduleController::class, 'storeShiftTemplate'])->name('receptionist.shift-templates.store');
    Route::put('/shift-templates/{template}', [ScheduleController::class, 'updateShiftTemplate'])->name('receptionist.shift-templates.update');
    Route::delete('/shift-templates/{template}', [ScheduleController::class, 'destroyShiftTemplate'])->name('receptionist.shift-templates.destroy');

    // Bulk exception store for multi-cell grid edits
    Route::post('/schedule-exception/bulk-store', [ScheduleController::class, 'storeExceptionBulk'])->name('receptionist.schedule-exception.bulk-store');

    // Delete individual exception
    Route::delete('/schedule-exception/{exception}', [ScheduleController::class, 'deleteException'])->name('receptionist.schedule.exception.delete');
});

// ==================== ADMIN ====================
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin-dashboard', [AdminController::class, 'dashboard'])->name('admin-dashboard');
});

// ─── Admin View Routes ───
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Users
    Route::get('/users', [AdminController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [AdminController::class, 'store'])->name('admin.users.store');
    Route::put('/users/{user}', [AdminController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    Route::put('/profile/update', [AdminController::class, 'updateProfile'])->name('admin.profile.update');
    Route::put('/users/{user}/deactivate', [AdminController::class, 'deactivate'])->name('admin.users.deactivate');
    Route::put('/users/{user}/reactivate', [AdminController::class, 'reactivate'])->name('admin.users.reactivate');

    // Services
    Route::get('/services', [AdminController::class, 'servicesIndex'])->name('admin.services.index');
    Route::post('/services', [AdminController::class, 'servicesStore'])->name('admin.services.store');
    Route::put('/services/{service}', [AdminController::class, 'servicesUpdate'])->name('admin.services.update');
    Route::delete('/services/{service}', [AdminController::class, 'servicesDestroy'])->name('admin.services.destroy');

    // Categories
    Route::get('/categories', [AdminController::class, 'categoriesIndex'])->name('admin.categories.index');
    Route::post('/categories', [AdminController::class, 'categoriesStore'])->name('admin.categories.store');
    Route::put('/categories/{category}', [AdminController::class, 'categoriesUpdate'])->name('admin.categories.update');
    Route::delete('/categories/{id}', [AdminController::class, 'categoriesDestroy'])->name('admin.categories.destroy');

    // Rooms
    Route::get('/rooms', [AdminController::class, 'roomsIndex'])->name('admin.rooms.index');
    Route::post('/rooms', [AdminController::class, 'roomsStore'])->name('admin.rooms.store');
    Route::put('/rooms/{room}', [AdminController::class, 'roomsUpdate'])->name('admin.rooms.update');
    Route::delete('/rooms/{room}', [AdminController::class, 'roomsDestroy'])->name('admin.rooms.destroy');

    // Landing permissions
    Route::put('/receptionists/{user}/toggle-landing', [AdminController::class, 'toggleLandingPermission'])->name('admin.receptionist.toggle-landing');

    // Sales
    Route::get('/sales', [SalesReportController::class, 'index'])->name('admin.sales');
    Route::get('/sales/tx-log', [SalesReportController::class, 'transactionLogFragment'])->name('admin.sales.tx-log');
    Route::get('/sales/daily-report-pdf', [SalesReportController::class, 'dailyReportPdf'])->name('admin.sales.daily-report-pdf');
    Route::get('/sales/business-report-pdf', [SalesReportController::class, 'businessReportPdf'])->name('admin.sales.business-report-pdf');
    Route::post('/sales/ai-chat', [SalesReportController::class, 'aiChat'])->name('admin.sales.ai-chat');

    // Appointments
    Route::get('/appointments', [AdminController::class, 'appointments'])->name('admin.appointments');

    // ─── SCHEDULE VIEW ROUTES ───
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('admin.schedules');
    Route::get('/shift-templates', [ScheduleController::class, 'templates'])->name('admin.shift-templates.index');
    Route::get('/api/staff/{staff}/schedule', [ScheduleController::class, 'staffScheduleApi'])->name('admin.api.staff.schedule');
});

// ─── Admin Schedule Edit Routes ───
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::post('/schedules/bulk-update', [ScheduleController::class, 'bulkUpdate'])->name('admin.schedules.bulk-update');
    // FIX: Route order matters! Bulk route must come BEFORE the parameterized route
    Route::post('/schedules/template/bulk', [ScheduleController::class, 'applyTemplateBulk'])->name('admin.schedules.template.bulk');
    // FIX: Changed from /schedules/template/{user} to /schedules/template/apply/{user}
    Route::post('/schedules/template/apply/{user}', [ScheduleController::class, 'applyTemplate'])->name('admin.schedules.template');
    Route::post('/schedules/block', [ScheduleController::class, 'quickBlock'])->name('admin.schedules.block');
    Route::post('/schedules/move-shift', [ScheduleController::class, 'moveShift'])->name('admin.schedules.move-shift');
    Route::delete('/schedule-exception/{exception}', [ScheduleController::class, 'deleteException'])->name('admin.schedule.exception.delete');
    Route::put('/receptionists/{user}/toggle-permission', [ScheduleController::class, 'toggleReceptionistPermission'])->name('admin.receptionist.toggle');
    Route::post('/schedule-template/update', [ScheduleController::class, 'updateTemplate'])->name('admin.schedule-template.update');
    Route::post('/schedule-exception/store', [ScheduleController::class, 'storeException'])->name('admin.schedule-exception.store');

    // Reusable ShiftTemplate CRUD
    Route::post('/shift-templates', [ScheduleController::class, 'storeShiftTemplate'])->name('admin.shift-templates.store');
    Route::put('/shift-templates/{template}', [ScheduleController::class, 'updateShiftTemplate'])->name('admin.shift-templates.update');
    Route::delete('/shift-templates/{template}', [ScheduleController::class, 'destroyShiftTemplate'])->name('admin.shift-templates.destroy');

    // Bulk exception store for multi-cell grid edits
    Route::post('/schedule-exception/bulk-store', [ScheduleController::class, 'storeExceptionBulk'])->name('admin.schedule-exception.bulk-store');
});

// ==================== LANDING EDITOR (Admin + Authorized Receptionists) ====================
Route::middleware(['auth'])->group(function () {
    Route::get('/landing-editor', [AdminController::class, 'landingEditor'])->name('admin.landing.editor');
    Route::put('/landing-editor', [AdminController::class, 'landingUpdate'])->name('admin.landing.update');
});

// ==================== ATTENDANCE ====================
Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/today', [AttendanceController::class, 'today'])->name('attendance.today');
    Route::post('/attendance/bulk-mark', [AttendanceController::class, 'bulkMark'])->name('attendance.bulk-mark');
    Route::get('/admin/attendance', [AttendanceController::class, 'report'])->name('attendance.report');
    Route::patch('/admin/attendance/toggle-permission/{user}', [AttendanceController::class, 'togglePermission'])->name('attendance.toggle-permission');
    Route::post('/api/attendance/quick-checkin/{staff}', [AttendanceController::class, 'quickCheckIn'])->name('attendance.quick-checkin');
    Route::post('/api/attendance/quick-checkout/{staff}', [AttendanceController::class, 'quickCheckOut'])->name('attendance.quick-checkout');
});

// ==================== NOTIFICATIONS (Single Definition) ====================
Route::middleware(['web', 'auth'])->prefix('api/notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('api.notifications');
    Route::get('/count', [NotificationController::class, 'count'])->name('api.notifications.count');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.read-all');
});