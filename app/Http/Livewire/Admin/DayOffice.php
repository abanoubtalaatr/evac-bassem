<?php

namespace App\Http\Livewire\Admin;

use Carbon\Carbon;
use Livewire\Component;
use function App\Helpers\canCloseDay;
use function App\Helpers\checkDayClosed;
use function App\Helpers\checkDayRestart;
use function App\Helpers\checkDayStart;
use function App\Helpers\currentDayForOffice;


class DayOffice extends Component
{
    public $page_title;
    public $disabledButtonDayStart = false;
    public $disabledButtonDayEnd = false;
    public $disabledButtonDayRestartDay = false;
    public $message = '';

    public function mount()
    {
        $this->page_title = __('admin.day_office');

        if((currentDayForOffice(1) && checkDayClosed(1)) || checkDayStart(1)) {
            $this->disabledButtonDayStart = true;
        }

        $this->disabledButtonDayEnd = checkDayClosed(1);

        if(checkDayRestart(1)) {
            $this->disabledButtonDayStart = true;
            $this->disabledButtonDayEnd = false;
        }


    }

    public function startDay()
    {
        $dayOffice = currentDayForOffice(1);

        if(!$dayOffice) {
            \App\Models\DayOffice::query()->create([
                'admin_id' => auth('admin')->id(),
                'office_id' => 1,
                'day_start' => Carbon::today(),
                'start_time' => Carbon::now()->format('H:i:s'),
                'end_time' => null,
                'day_status' => '1',
            ]);
        }

       return redirect()->to(route('admin.day_office'));
    }

    public function endDay()
    {
        $officeDay = currentDayForOffice(1);

        if(!checkDayStart(1)){
            $this->message = 'Please start your day first';
            return ;
        }
        if(canCloseDay(1)){
            $officeDay->update([
                'end_time' => Carbon::now()->format('H:i:s'),
                'day_status' => "0",
            ]);
            return redirect()->to(route('admin.day_office'));
        }
        $this->message = "You can not close the day because still exist appraisal applications";
    }

    public function restartDay()
    {
        $officeDay = currentDayForOffice(1);

        $officeDay->update([
            'restart_at' => Carbon::now()->format('H:i:s'),
            'day_status' => "2",
        ]);
        return redirect()->to(route('admin.day_office'));
    }

    public function render()
    {
        return view('livewire.admin.day-office')->layout('layouts.admin');
    }
}
