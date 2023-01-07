<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PatientInformation extends Model
{
    protected $guarded = [];
    protected $table = 'patient_informations';

    /**
     * @return string[]
     */
    public function initialPatientInformationKeys(): array
    {
        return ['name', 'code_status', 'gender', 'allergies', 'age', 'weight', 'weight_units', 'dob', 'bmi'];
    }

    /**
     * @return string[]
     */
    public function updatedPatientInformationKeys(): array
    {
        return ['updated_weight', 'updated_bmi','first_application_of_updated_information'];
    }

    /**
     * @return string[]
     */
    public function validCodeStatuses(): array
    {
        return ['full_code', 'dnr'];
    }

    /**
     * @return string[]
     */
    public function validWeightUnits(): array
    {
        return ['lb', 'kg'];
    }
}
