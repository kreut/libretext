<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-reset-course"/>
    <b-modal
      id="modal-reset-course"
      ref="modal"
      :title="`Reset ${course.name}`"
      size="lg"
      @show="getScores"
    >
      <b-form ref="form">
        <p>
          Please confirm that you would like to reset the following course:</p>
        <p class="text-center"><strong>{{ course.name }}</strong></p>

        <RequiredText :plural="false"/>
        <b-form-group
          label-cols-sm="1"
          label-cols-lg="2"
          label="Confirmation"
          label-for="Confirmation"
        >
          <template slot="label">
            Confirmation*
          </template>
          <b-form-input
            id="confirmation"
            v-model="resetCourseForm.confirmation"
            class="col-8"
            required
            placeholder="Please enter the the name of the course."
            type="text"
            :class="{ 'is-invalid': resetCourseForm.errors.has('confirmation') }"
            @keydown="resetCourseForm.errors.clear('confirmation')"
          />
          <has-error :form="resetCourseForm" field="confirmation"/>
        </b-form-group>
      </b-form>
      <b-form-group v-if="showDownload">
        <b-form-checkbox
          id="checkbox-1"
          v-model="resetCourseForm.understand_scores_removed"
          name="understand_scores_removed"
          value="1"
          unchecked-value="0"
        >
          I understand that after resetting the course all scores will be removed.  Optionally, I may download the gradebook to preserve the scores.
        </b-form-checkbox>
        <input type="hidden" class="form-control is-invalid">
        <div v-if="!resetCourseForm.understand_scores_removed" class="help-block invalid-feedback">
          {{ resetCourseForm.errors.get('understand_scores_removed') }}
        </div>
      </b-form-group>

      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-reset-course')"
        >
          Cancel
        </b-button>
        <download-excel
          v-if="showDownload"
          :data="downloadRows"
          :fetch="fetchData"
          :fields="downloadFields"
          worksheet="My Worksheet"
          type="csv"
          name="all_scores.csv"
        >
          <b-button v-if="showDownload"
                    variant="success"
                    size="sm"
                    @click="resetCourseForm.downloaded_gradebook = 1"
          >
            Download Gradebook
          </b-button>
        </download-excel>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          :disabled="processingResettingCourse"
          @click="submitResetCourse"
        >
          <span v-if="!processingResettingCourse">Reset Course</span>
          <span v-if="processingResettingCourse"><b-spinner small type="grow"/>
            Resetting Course...
          </span>
        </b-button>
      </template>
    </b-modal>
  </div>

</template>

<script>
import AllFormErrors from '~/components/AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import Form from 'vform'
import axios from 'axios'

export default {
  name: 'ResetCourse',
  components: { AllFormErrors },
  props: {
    showDownload: {
      type: Boolean,
      default: true
    },
    course: {
      type: Object,
      default: () => {
      }
    },
    courseId: {
      type: Number,
      default: 0
    },
    parentReloadData: {
      type: Function,
      default: () => {
      }
    }
  },
  data: () => ({
    downloadRows: [],
    downloadFields: {},
    allFormErrors: [],
    processingResettingCourse: false,
    resetCourseForm: new Form({
      confirmation: '',
      understand_scores_removed: 0
    })
  }),
  methods: {
    async getScores () {
      if (!this.showDownload) {
        return false
      }
      try {
        const { data } = await axios.get(`/api/scores/${this.courseId}/0`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.downloadFields = data.download_fields
        this.downloadRows = data.download_rows
      } catch (error) {
        alert(error.message)
      }
    },
    async fetchData () {
      const { data } = await axios.get(`/api/scores/${this.courseId}/0`)
      console.log(data)
      return data.download_rows.sort((a, b) => (a.name > b.name) - (a.name < b.name))// sort in ascending order
    },
    async submitResetCourse () {
      this.processingResettingCourse = true
      try {
        const { data } = await this.resetCourseForm.delete(`/api/courses/${this.courseId}/reset`)
        this.$noty[data.type](data.message)
        this.resetCourseForm.confirmation = ''
        this.$bvModal.hide('modal-reset-course')
        this.processingResettingCourse = false
        if (data.type === 'success') {
          this.$emit('parentReloadData')
        }
      } catch (error) {
        this.processingResettingCourse = false
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          fixInvalid()
          this.allFormErrors = this.resetCourseForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-reset-course')
        }
      }
      this.resetCourseForm.confirmation = ''
      this.processingResettingCourse = false
    }
  }
}
</script>
