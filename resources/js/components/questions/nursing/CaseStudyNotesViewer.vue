<template>
  <div>
    <b-tabs small>
      <b-tab v-for="(item,caseStudyNotesIndex) in caseStudyNotes"
             :key="`case-study-notes-${caseStudyNotesIndex}`"
      >
        <template #title>
          <span v-if="item.updated_information" :id="`item-${caseStudyNotesIndex}`" class="text-success pr-1"><b-icon-check-circle-fill/></span>{{
            item.title
          }}
        </template>
        <div class="mt-2">
          <div v-if="item.title === 'Patient Information'">
            <b-container>
              <b-row v-for="(patientInformationItem, index) in patientInformation"
                     :key="`patient-information-${index}`"
              >
                <b-col v-if="patientInformation[index*2]">
                  {{ patientInformation[index * 2].label }} {{ patientInformation[index * 2].value }}
                </b-col>
                <b-col v-if="patientInformation[index*2+1]">
                  {{ patientInformation[index * 2 + 1].label }} {{ patientInformation[index * 2 + 1].value }}
                </b-col>
              </b-row>
            </b-container>
          </div>
          <div v-if="item.title !== 'Patient Information'">
            <div v-html="item.text"/>
            <div v-if="!item.text">
              No notes are available

            </div>
          </div>
        </div>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import { codeStatusOptions } from '~/helpers/CaseStudyNotes'

export default {
  name: 'CaseStudyNotesViewer',
  props: {
    caseStudyNotes: {
      type: Array,
      default: () => {
      }
    }
  },
  data: () => ({
    codeStatusOptions: codeStatusOptions,
    patientInformation: []
  }),
  mounted () {
    this.getPatientInformation()
  },
  methods: {
    getPatientInformation () {
      let patientInformation = this.caseStudyNotes.find(item => item.title === 'Patient Information').text
      console.log(patientInformation)
      this.patientInformation = []
      if (patientInformation.name) {
        this.patientInformation.push({ label: 'Name:', value: patientInformation.name })
      }

      if (codeStatusOptions.find(codeStatus => codeStatus.value === patientInformation.code_status).text) {
        this.patientInformation.push({
          label: 'Code Status:',
          value: codeStatusOptions.find(codeStatus => codeStatus.value === patientInformation.code_status).text
        })
      }
      if (patientInformation.gender) {
        this.patientInformation.push({ label: 'Gender:', value: patientInformation.gender })
      }
      if (patientInformation.allergies) {
        this.patientInformation.push({ label: 'Allergies:', value: patientInformation.allergies })
      }
      if (patientInformation.age) {
        this.patientInformation.push({ label: 'Age:', value: patientInformation.age })
      }
      if (patientInformation.weight) {
        this.patientInformation.push({ label: 'Weight:', value: patientInformation.weight })
      }
      if (patientInformation.dob) {
        this.patientInformation.push({ label: 'DOB:', value: patientInformation.dob })
      }
      if (patientInformation.bmi) {
        this.patientInformation.push({ label: 'BMI:', value: patientInformation.bmi })
      }
    }
  }
}
</script>

<style scoped>

</style>
