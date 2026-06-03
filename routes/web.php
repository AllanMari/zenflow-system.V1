<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReceptionistController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SalesReportController;

// ==================== PUBLIC ROUTES (NO LOGIN REQUIRED) ====================
Route::middleware('web')->group(function () {
    Route::get('/', [BookingController::class, 'landing'])->name('landing');
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('customer.register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/terms', fn() => view('terms'))->name('terms');

    // Booking (guests can book without logging in)
    Route::get('/book', [BookingController::class, 'wizard'])->name('booking.wizard');
    Route::get('/api/booking/slots', [BookingController::class, 'getSlots'])->name('booking.slots');
    Route::get('/api/occupied-slots', [BookingController::class, 'occupiedSlots'])->name('api.occupied-slots');
    Route::post('/book', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/book/confirmation/{appointment}', [BookingController::class, 'confirmation'])->name('booking.confirmation');

    // Customer lookup (minimal, opt-in)
    Route::get('/api/customers/lookup', [BookingController::class, 'customerLookup'])->name('api.customers.lookup');

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

Route::middleware(['auth', 'role:receptionist'])->prefix('receptionist')->group(function () {
    Route::get('/dashboard', [ReceptionistController::class, 'desk'])->name('receptionist.dashboard');
    Route::get('/booking/{appointment}', [ReceptionistController::class, 'booking'])->name('receptionist.booking');
    Route::post('/booking/{appointment}/confirm', [ReceptionistController::class, 'confirm'])->name('receptionist.confirm');
    Route::post('/booking/{appointment}/cancel', [ReceptionistController::class, 'cancel'])->name('receptionist.cancel');
    Route::post('/appointments/{appointment}/complete', [ReceptionistController::class, 'complete'])->name('receptionist.complete');
    Route::get('/pending', [ReceptionistController::class, 'pending'])->name('receptionist.pending');

    // ========== SCHEDULING ==========
    Route::get('/schedules', [ReceptionistController::class, 'schedules'])->name('receptionist.schedules');
    Route::post('/schedules/bulk-update', [ReceptionistController::class, 'bulkUpdateSchedules'])->name('receptionist.schedules.bulk-update');
    Route::post('/schedules/template/{user}', [ReceptionistController::class, 'applyTemplate'])->name('receptionist.schedules.template');
    Route::post('/schedules/block', [ReceptionistController::class, 'quickBlock'])->name('receptionist.schedules.block');
    Route::post('/schedules/{user}', [ReceptionistController::class, 'updateSchedule'])->name('receptionist.schedules.update');

    // ========== MID-SESSION EXTRA SERVICES ==========
    Route::post('/appointments/{appointment}/add-extra', [ReceptionistController::class, 'addExtraService'])->name('receptionist.add-extra');

    // ========== NO-SHOW DEPOSIT HANDLING ==========
    Route::post('/appointments/{appointment}/no-show', [ReceptionistController::class, 'handleNoShow'])->name('receptionist.no-show');

    Route::get('/active', [ReceptionistController::class, 'active'])->name('receptionist.active');

    Route::get('/sales', [SalesReportController::class, 'index'])->name('receptionist.sales');
    Route::get('/sales/tx-log', [SalesReportController::class, 'transactionLogFragment'])->name('receptionist.sales.tx-log');    

    Route::get('/booking/{appointment}/rooms', [ReceptionistController::class, 'availableRooms'])->name('receptionist.rooms.available');

    // Quick Book
    Route::get('/quick-book', [BookingController::class, 'quickBook'])->name('receptionist.quick-book');

    Route::get('/api/booking/next-slots', [BookingController::class, 'nextSlots'])->name('api.next-slots');

        Route::post('/sales/ai-chat', [SalesReportController::class, 'aiChat'])->name('receptionist.sales.ai-chat');
});

// ==================== ADMIN ====================
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin-dashboard', [AdminController::class, 'dashboard'])->name('admin-dashboard');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Users
    Route::get('/users', [AdminController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [AdminController::class, 'store'])->name('admin.users.store');
    Route::put('/users/{user}', [AdminController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    Route::put('/profile/update', [AdminController::class, 'updateProfile'])->name('admin.profile.update');

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

    // ========== SCHEDULING ==========
    Route::get('/schedules', [AdminController::class, 'scheduleManagement'])->name('admin.schedules');
    Route::post('/schedules/bulk-update', [AdminController::class, 'bulkUpdateSchedules'])->name('admin.staff.schedule.bulk-update');
    Route::post('/schedules/template/{user}', [AdminController::class, 'applyTemplate'])->name('admin.schedules.template');
    Route::post('/schedules/block', [AdminController::class, 'quickBlock'])->name('admin.schedules.block');
    Route::post('/schedule-exception', [AdminController::class, 'storeException'])->name('admin.schedule.exception');
    Route::delete('/schedule-exception/{exception}', [AdminController::class, 'deleteException'])->name('admin.schedule.exception.delete');

    // Receptionist permissions
    Route::put('/receptionists/{user}/toggle-permission', [AdminController::class, 'toggleReceptionistPermission'])->name('admin.receptionist.toggle');

    // Shift templates
    Route::get('/shift-templates', [AdminController::class, 'templatesIndex'])->name('admin.shift-templates.index');
    Route::post('/shift-templates', [AdminController::class, 'templatesStore'])->name('admin.shift-templates.store');
    Route::put('/shift-templates/{template}', [AdminController::class, 'templatesUpdate'])->name('admin.shift-templates.update');
    Route::delete('/shift-templates/{template}', [AdminController::class, 'templatesDestroy'])->name('admin.shift-templates.destroy');

    // Landing permissions
    Route::put('/receptionists/{user}/toggle-landing', [AdminController::class, 'toggleLandingPermission'])->name('admin.receptionist.toggle-landing');
    Route::get('/sales', [SalesReportController::class, 'index'])->name('admin.sales');
    Route::get('/sales/tx-log', [SalesReportController::class, 'transactionLogFragment'])->name('admin.sales.tx-log');

    // Rooms
    Route::get('/rooms', [AdminController::class, 'roomsIndex'])->name('admin.rooms.index');
    Route::post('/rooms', [AdminController::class, 'roomsStore'])->name('admin.rooms.store');
    Route::put('/rooms/{room}', [AdminController::class, 'roomsUpdate'])->name('admin.rooms.update');
    Route::delete('/rooms/{room}', [AdminController::class, 'roomsDestroy'])->name('admin.rooms.destroy');

    Route::put('/admin/users/{user}/deactivate', [AdminController::class, 'deactivate'])->name('admin.users.deactivate');
    Route::put('/admin/users/{user}/reactivate', [AdminController::class, 'reactivate'])->name('admin.users.reactivate');

    Route::post('/sales/ai-chat', [SalesReportController::class, 'aiChat'])->name('admin.sales.ai-chat');
});

// ==================== LANDING EDITOR (Admin + Authorized Receptionists) ====================
Route::middleware(['auth'])->group(function () {
    Route::get('/landing-editor', [AdminController::class, 'landingEditor'])->name('admin.landing.editor');
    Route::put('/landing-editor', [AdminController::class, 'landingUpdate'])->name('admin.landing.update');
});