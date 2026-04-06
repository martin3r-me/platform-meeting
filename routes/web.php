<?php

use Platform\Meetings\Livewire\Dashboard;
use Platform\Meetings\Livewire\Meeting;
use Platform\Meetings\Livewire\CreateMeeting;
use Platform\Meetings\Livewire\CreateSeries;
use Platform\Meetings\Livewire\MeetingSeriesView;

Route::get('/', Dashboard::class)->name('meetings.dashboard');
Route::get('/create', CreateMeeting::class)->name('meetings.create');
Route::get('/series/create', CreateSeries::class)->name('meetings.series.create');
Route::get('/series/{meetingSeries}', MeetingSeriesView::class)->name('meetings.series.show');
Route::get('/meetings/{meeting}', Meeting::class)->name('meetings.show');
