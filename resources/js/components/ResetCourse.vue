<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-reset-course"/>
    <b-modal :id="`modal-resetting-course`"
             hide-footer
             no-close-on-backdrop
             no-close-on-esc
             size="lg"
             :title="`Resetting ${course.name}`"
    >
      <b-alert variant="info" :show="processingResettingCourse">
        <b-spinner small type="grow"/>
        Processing...please be patient. Resetting the course may take up to 30 seconds to complete.
      </b-alert>
      <b-alert :variant="resettingCourseMessageData.type === 'success' ? 'success': 'danger'"
               :show="!processingResettingCourse"
      >
        <div v-html="resettingCourseMessageData.message"/>
      </b-alert>
    </b-modal>
    <b-modal
      id="modal-reset-course"
      ref="modal"
      :title="`Reset ${course.name}`"
      size="lg"
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
          <template v-slot:label>
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
          I understand that after resetting the course all scores will be removed. Optionally, I may download the
          gradebook to preserve the scores.
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

        <a v-show="false"
           id="download-scores"
           :href="`/api/scores/${courseId}/0/1`"

        >
          Download Scores
        </a>
        <b-button v-if="showDownload"
                  size="sm"
                  variant="outline-primary"
                  @click="downloadScores()"
        >
          Download Scores
        </b-button>
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
import { initCentrifuge } from '../helpers/Centrifuge'

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
    resettingCourseMessageData: {},
    allFormErrors: [],
    processingResettingCourse: false,
    resetCourseForm: new Form({
      confirmation: '',
      understand_scores_removed: 0
    })
  }),
  beforeDestroy () {
    try {
      if (this.centrifuge) {
        this.centrifuge.disconnect()
      }
    } catch (error) {
      // won't be a function for all the other ones that haven't been defined on the page
    }
  },
  methods: {
    downloadScores () {
      this.resetCourseForm.downloaded_gradebook = true
      document.getElementById('download-scores').click()
    },
    async submitResetCourse () {
      this.processingResettingCourse = true
      this.centrifuge = await initCentrifuge()
      const sub = this.centrifuge.newSubscription(`reset-course-${this.courseId}`)
      const courseReset = async (ctx) => {
        this.resettingCourseMessageData = ctx.data
        this.resetCourseForm.confirmation = ''
        this.processingResettingCourse = false
        this.centrifuge.disconnect()
        this.$emit('parentReloadData')
      }
      sub.on('publication', function (ctx) {
        courseReset(ctx)
      }).subscribe()
      try {
        const { data } = await this.resetCourseForm.delete(`/api/courses/${this.courseId}/reset`)
        if (data.type === 'error') {
          this.$noty.error(data.error)
        } else {
          this.$bvModal.show('modal-resetting-course')
          this.$bvModal.hide('modal-reset-course')
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
    }
  }
}
</script>
