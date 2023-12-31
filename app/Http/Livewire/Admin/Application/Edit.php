<?php

namespace App\Http\Livewire\Admin\Application;

use App\Http\Livewire\Traits\ValidationTrait;
use App\Models\Agent;
use App\Models\Application;
use App\Models\BlackListPassport;
use App\Models\Setting;
use App\Models\VisaProvider;
use App\Models\VisaType;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Livewire\Component;

class Edit extends Component
{
    use ValidationTrait;
    public $name;
    public $form;
    public $is_active;
    public $perPage =10;
    public $search;
    public $application;
    public $visaTypes, $visaProviders, $travelAgents ;
    public $isChecked = false, $showAlertBlackList = false;
    public $passportNumber = [];
    public $passportApplications;
    public $numberOfDaysToCheckVisa=90;
    public $isEdit = false;
    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['showApplication'];

    public function mount(Application $application)
    {
        $this->page_title = __('admin.applications');
        $this->visaTypes = VisaType::query()->get();
        $this->visaProviders = VisaProvider::query()->get();
        $this->travelAgents = Agent::query()->where('is_active', 1)->get();

        $application = Application::query()->find(request()->application);
        $this->form = $application->toArray();
        $this->passportNumber = $application->passport_no;
        $this->form['expiry_date'] = Carbon::parse($application->expiry_date)->format('Y-m-d');
        $this->application = $application;
        $this->page_title = __('admin.applications_edit');
        if($application->travel_agent_id){

            $this->isChecked = true;
            $this->isEdit = true;
        }
    }


    public function chooseTravelAgent()
    {

        $this->isChecked = !$this->isChecked;
    }

    public function updatedFormVisaTypeId()
    {
        $setting = Setting::query()->first();

        $value = (int) filter_var($setting->vat_rate, FILTER_SANITIZE_NUMBER_INT);

        $visaType = VisaType::query()->find($this->form['visa_type_id']);
        $amount = $visaType->dubai_fee + $visaType->service_fee + ($value / 100 * $visaType->service_fee);

        $this->form['amount'] = $amount;
    }
    public function store()
    {
        $this->validate();

        if($this->checkPassportInBlackList()) {
            $this->emit('openBlackListModal');
            return;
        }

        if ($this->checkExpiryPassport()){
            $this->emit('showExpiryPopup');
            return;
        }

        if($this->checkPassportHasMoreThanOneApplication()) {
            $this->emit('showMultipleApplicationsPopup');
            return;
        }

        $this->save();
    }

    public function checkPassportHasMoreThanOneApplication()
    {
        $settings = Setting::query()->first();
        $numberOfDaysToCheckVisa = 90;
        if($settings) {
            $numberOfDaysToCheckVisa = $settings->no_of_days_to_check_visa;
        }
        $this->numberOfDaysToCheckVisa =$numberOfDaysToCheckVisa;

        $previousApplications = Application::where('passport_no', $this->form['passport_no'])
            ->where('created_at', '>', now()->subDays($numberOfDaysToCheckVisa))
            ->get();


        if ($previousApplications->count() > 1) {
            $this->passportApplications = $previousApplications;
            return true;
        }
        return false;
    }
    public function checkExpiryPassport()
    {
        $expiryDateTime = new \DateTime($this->form['expiry_date']);
        $difference = now()->diff($expiryDateTime)->days;

        if ($difference < 180) {
            return true;
        }
        return false;
    }

    public function save()
    {
        $this->validate();
        $data = $this->form;

        if(isset($data['agent_id'])) {
            $data['travel_agent_id'] = $data['agent_id'];
            unset($data['agent_id']);
        }
        $this->application->update(Arr::except($data, ['created_at', 'updated_at']));
        session()->flash('success',__('admin.edit_successfully'));

        return redirect()->to(route('admin.applications.revise'));
    }

    public function checkPassportInBlackList()
    {
        $blackList = BlackListPassport::query()->where('passport_number', $this->form['passport_no'])->first();

        if($blackList) {
            return true;
        }
        return false;
    }

    public function checkPassportNumber()
    {
        $existingPassport = Application::where('passport_no', $this->passportNumber)->first();

        if ($existingPassport) {
            $this->form['passport_no'] = $existingPassport->passport_no??$this->passportNumber;
            $this->form['expiry_date'] = Carbon::parse($existingPassport->expiry_date)->format('Y-m-d');
            $this->form['first_name'] = $existingPassport->first_name;
            $this->form['last_name'] = $existingPassport->last_name;
        }else{
            $this->form['passport_no'] = $this->passportNumber;
        }

    }

    public function getRules(){
        return [
            'form.visa_type_id' => 'required|max:500',
            'form.visa_provider_id' => 'required|max:500',
            'form.passport_no' => 'required',
            'form.expiry_date' => 'required|date',
            'form.travel_agent_id' => 'nullable',
            'form.first_name' => 'required',
            'form.last_name' => 'required',
            'form.title' => ['nullable', 'in:Mr,Mrs,Ms'],
            'form.notes' => 'nullable|max:500',
            'form.amount' => 'nullable|numeric|max:500',
        ];
    }

    public function showAgent($id)
    {
        $this->form = [];
        $this->applicationId = $id;
        $this->application = Application::query()->find($id);

        $this->form = $this->application->toArray();

        $this->emit("showApplicationModal", $id);
    }

    public function resetApplication()
    {
        return redirect()->to(route('admin.applications.store'));
    }
    public function updatedFormPassportNo()
    {
        $this->searchResults = []; // Clear previous search results

        if ($this->form['passport_no'] !== '') {
            $foundUser = Application::where('passport_no', $this->form['passport_no'])->first();
            if ($foundUser) {
                $this->form['first_name'] = $foundUser->first_name;
                $this->form['last_name'] = $foundUser->last_name;
            }
        }
    }


    public function render()
    {
        return view('livewire.admin.application.edit')->layout('layouts.admin');
    }
}
