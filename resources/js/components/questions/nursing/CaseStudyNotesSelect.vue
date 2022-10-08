<template>
  <div>
    <b-form-group>
      <b-form-checkbox-group
        id="checkbox-group-1"
        v-model="selected"
        name="selected case studies"
      >
        <div class="row">
          <span class="mr-2">Initial condition:</span>
          <div v-for="(option,caseStudyIndex) in caseStudyNotesOptions.filter(notes => notes.version === 0)"
               :key="`${option.value}-${caseStudyIndex}-${option.version}`"
          >
            <b-form-checkbox
              :value="option.value"
              :selected="selected.includes(option.value)"
            >
              {{ option.text }}
            </b-form-checkbox>
          </div>
        </div>
        <div class="row">
          <span class="mr-2">Updated Information:</span>
          <div v-for="(option,caseStudyIndex) in caseStudyNotesOptions.filter(notes => notes.version === 1)"
               :key="`${option.value}-${caseStudyIndex}-${option.version}`"
          >
            <b-form-checkbox
              :value="option.value"
              :selected="selected.includes(option.value)"
            >
              {{ option.text }}
            </b-form-checkbox>
          </div>
          <b-button size="sm" variant="primary" @click="updateCaseStudyNotes">
            Update
          </b-button>
        </div>
      </b-form-checkbox-group>
    </b-form-group>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CaseStudyNotesSelect',
  props: {
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    caseStudyNotes: {},
    selected: [],
    caseStudyNotesOptions: []
  }),
  watch: {
    selected: function (newValue, oldValue) {
      if (!oldValue.length) {
        this.updateCaseStudyNotes()
        return false
      }
      if (newValue.length > oldValue.length) {
        let selected
        for (let i = 0; i < newValue.length; i++) {
          if (!oldValue.includes(newValue[i])) {
            selected = newValue[i]
          }
        }
        let selectedToRemove
        let selectedOption = this.caseStudyNotesOptions.find(notes => notes.value === selected)
        console.log(selectedOption)
        for (let i = 0; i < this.caseStudyNotesOptions.length; i++) {
          let option = this.caseStudyNotesOptions[i]
          console.log(option.type)
          if (option !== selectedOption && option.text === selectedOption.text) {
            selectedToRemove = option.value
          }
        }
        if (selectedToRemove) {
          this.selected = this.selected.filter(item => item !== selectedToRemove)
        }
      }
    }
  },
  mounted () {
    this.getSelectedCaseStudyNotes()
  },
  methods: {
    async updateCaseStudyNotes () {
      try {
        const { data } = await axios.patch(`/api/case-study-notes/assignment/${this.assignmentId}/question/${this.questionId}`, { selected: this.selected })
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          let selectedCaseStudyNotes = this.caseStudyNotes.filter(item => this.selected.includes(item.id))
          this.$emit('setCaseStudyNotes', selectedCaseStudyNotes)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getSelectedCaseStudyNotes () {
      try {
        const { data } = await axios.get(`/api/case-study-notes/assignment/${this.assignmentId}/question/${this.questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.caseStudyNotes = data.case_study_notes
        for (let i = 0; i < data.case_study_notes.length; i++) {
          let caseStudyNotes = data.case_study_notes[i]
          console.log(caseStudyNotes)
          this.caseStudyNotesOptions.push({
            text: caseStudyNotes.type,
            value: caseStudyNotes.id,
            version: caseStudyNotes.version
          })
          if (caseStudyNotes.selected) {
            this.selected.push(caseStudyNotes.id)
          }
        }
        let selectedCaseStudyNotes = this.caseStudyNotes.filter(item => this.selected.includes(item.id))
        this.$emit('setCaseStudyNotes', selectedCaseStudyNotes)
        console.log(data.case_study_notes)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
