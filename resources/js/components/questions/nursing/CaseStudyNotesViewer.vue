<template>
  <div>
    <b-tabs>
      <b-tab v-for="(item,caseStudyNotesIndex) in caseStudyNotes"
             :key="`case-study-notes-${caseStudyNotesIndex}`"
      >
        <template #title>
          <span v-if="item.updated_information" :id="`item-${caseStudyNotesIndex}`" class="text-success pr-1"><b-icon-check-circle-fill/></span>{{ item.title }}
        </template>
        <div class="mt-2">
          <div v-if="item.title === 'Patient Information'">
            <b-container>
              <b-row>
                <b-col>
                  Name: {{ item.text.name }}
                </b-col>
                <b-col>
                  Code Status: {{ codeStatusOptions.find(codeStatus => codeStatus.value === item.text.code_status).text }}
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  Gender: {{ item.text.gender }}
                </b-col>
                <b-col>
                  Allergies: {{ item.text.allergies }}
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  Age: {{ item.text.age }}
                </b-col>
                <b-col>
                  Weight: {{ item.text.weight }} {{ item.text.weight_units }}
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  DOB: {{ item.text.dob }}
                </b-col>
                <b-col>
                  BMI: {{ item.text.bmi }}
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
    codeStatusOptions: codeStatusOptions
  })
}
</script>

<style scoped>

</style>
