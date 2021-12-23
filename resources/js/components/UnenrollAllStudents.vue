<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-unenroll-all-students"/>
    <b-modal
      id="modal-unenroll-all-students"
      ref="modal"
      :title="`Unenroll All Students Fom ${course.name}`"

      size="lg"
    >
      <b-form ref="form">
        <b-alert show variant="danger">
          <span class="font-weight-bold">Warning! All of your students' submissions will be permanently removed.</span>
        </b-alert>
        <p>
          Please confirm that you would like to unenroll all students from:</p>
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
            v-model="unenrollAllStudentsForm.confirmation"
            class="col-8"
            aria-required="true"
            placeholder="Please enter the the name of the course."
            type="text"
            :class="{ 'is-invalid': unenrollAllStudentsForm.errors.has('confirmation') }"
            @keydown="unenrollAllStudentsForm.errors.clear('confirmation')"
          />
          <has-error :form="unenrollAllStudentsForm" field="confirmation"/>
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-unenroll-all-students')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          :disabled="processingUnenrollingAllStudents"
          @click="submitUnenrollAllStudents"
        >
          <span v-if="!processingUnenrollingAllStudents">Yes, unenroll all students!</span>
          <span v-if="processingUnenrollingAllStudents"><b-spinner small type="grow"/>
            Unenrolling students...
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

export default {
  name: 'UnenrollAllStudents',
  components: { AllFormErrors },
  props: {
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
    allFormErrors: [],
    processingUnenrollingAllStudents: false,
    unenrollAllStudentsForm: new Form({
      confirmation: ''
    })
  }),
  methods: {
    async submitUnenrollAllStudents () {
      this.processingUnenrollingAllStudents = true
      try {
        const { data } = await this.unenrollAllStudentsForm.delete(`/api/enrollments/courses/${this.courseId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.parentReloadData()
        }
        this.unenrollAllStudentsForm.confirmation = ''
        this.$bvModal.hide('modal-unenroll-all-students')
        this.processingUnenrollingAllStudents = false
      } catch (error) {
        this.processingUnenrollingAllStudents = false
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          fixInvalid()
          this.allFormErrors = this.unenrollAllStudentsForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-unenroll-all-student')
        }
      }
      this.unenrollStudentForm.confirmation = ''
      this.processingUnenrollingAllStudents = false
    }
  }
}
</script>
