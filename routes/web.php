<?php

use Platform\Meetings\Livewire\Dashboard;
use Platform\Meetings\Livewire\Meeting;
use Platform\Meetings\Livewire\Appointment;
use Platform\Meetings\Livewire\AgendaItem;
use Platform\Meetings\Livewire\CreateMeeting;
use Platform\Meetings\Models\Meeting as MeetingModel;

Route::get('/', Dashboard::class)->name('meetings.dashboard');
Route::get('/create', CreateMeeting::class)->name('meetings.create');

Route::get('/meetings/{meeting}', Meeting::class)
    ->name('meetings.show');

Route::get('/appointments/{appointment}', Appointment::class)
    ->name('meetings.appointments.show');

Route::get('/agenda-items/{agendaItem}', AgendaItem::class)
    ->name('meetings.agenda-items.show');

