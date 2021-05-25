<template>
  <div>
    <b-modal
      id="modal-unenroll-student"
      ref="modal"
      title="Unenroll Student"
      size="lg"
    >
      <b-form ref="form">
        <b-alert show variant="danger">
          <span class="font-weight-bold">Warning! All of this student's submissions will be permanently removed.</span>
        </b-alert>
        <p>
          <span class="font-italic">Please confirm that you would like to unenroll <strong>{{
              studentToUnenroll.name
            }}</strong> from
            <strong>{{ studentToUnenroll.section }}</strong>.</span>
        </p>
        <b-form-group
          id="confirmation"
          label-cols-sm="1"
          label-cols-lg="2"
          label="Confirmation"
          label-for="Confirmation"
        >
          <b-form-input
            id="confirmation"
            v-model="unenrollStudentForm.confirmation"
            class="col-6"
            placeholder="Please enter the student's full name."
            type="text"
            :class="{ 'is-invalid': unenrollStudentForm.errors.has('confirmation') }"
            @keydown="unenrollStudentForm.errors.clear('confirmation')"
          >
          </b-form-input>
          <has-error :form="unenrollStudentForm" field="confirmation"></has-error>
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          variant="danger"
          @click="cancelUnenrollStudent"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitUnenrollStudent"
        >
          Yes, unenroll the student!
        </b-button>
      </template>

    </b-modal>

    <b-modal
      id="modal-move-student"
      ref="modal"
      title="Move Student To New Section"
    >
      <b-alert show variant="info"><span class="font-weight-bold">The student's  submissions from the originating section will be removed
        if the associated assignment doesn't exist in the new section.</span>
      </b-alert>
      <p>
        <span class="font-italic">{{ studentToMove.name }} is currently enrolled in {{ studentToMove.section }}.</span>
      </p>
      <b-form ref="form">
        <b-form-group
          id="move_student"
          label-cols-sm="5"
          label-cols-lg="4"
          label="Move student"
          label-for="move student"
        >
          <div class="mb-2 mr-2">
            <b-form-select v-model="moveStudentForm.section_id"
                           :options="studentSectionOptions"
                           :class="{ 'is-invalid': moveStudentForm.errors.has('section_id') }"
                           @keydown="moveStudentForm.errors.clear('section_id')"
            />
            <has-error :form="moveStudentForm" field="section_id"/>
          </div>
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          variant="danger"
          @click="cancelMoveStudent"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitMoveStudent"
        >
          Yes, move the student!
        </b-button>
      </template>
    </b-modal>

    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading && user.role === 2">
        <b-card header="default" header-html="Students">
          <b-card-text>
            <div v-if="enrollments.length">
              <b-table striped hover
                       :fields="fields"
                       :items="enrollments"
              >
                <template v-slot:cell(name)="data">
                  <a href="#" @click="loginAsStudentInCourse(data.item.id)">{{ data.item.name }}</a>
                </template>
                <template v-slot:cell(email)="data">

                  <span :id="`email-${data.item.id}}`">{{ data.item.email }} </span> <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy(`email-${data.item.id}}`)"/>
        </span>
                </template>
                <template v-slot:cell(actions)="data">
                  <b-icon v-show="sectionOptions.length>1" icon="truck" @click="initMoveStudent(data.item)"/>
                  <b-icon icon="trash" @click="initUnenrollStudent(data.item)"/>
                </template>
              </b-table>
            </div>
            <div v-else>
              <b-alert show variant="info">
                <span class="font-weight-bold">You currently have no students enrolled in this course.</span>
              </b-alert>
            </div>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { loginAsStudentInCourse } from '~/helpers/LoginAsStudentInCourse'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'

export default {
  middleware: 'auth',
  components: {
    Loading,
    FontAwesomeIcon
  },
  data: () => ({
    copyIcon: faCopy,
    studentToUnenroll: {},
    unenrollStudentForm: new Form({
      confirmation: ''
    }),
    moveStudentForm: new Form({
      section_id: 0
    }),
    studentId: 0,
    sectionId: 0,
    studentSectionOptions: [],
    studentToMove: {},
    sectionToMoveTo: 0,
    sectionsOptions: [],
    enrollments: [],
    sectionOptions: [],
    graderFormType: 'addGrader',
    fields: [
      'name',
      'email',
      'enrollment_date',
      {
        key: 'section',
        label: 'Section'
      },
      'actions'

    ],
    isLoading: true,
    students: []
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.doCopy = doCopy
    this.loginAsStudentInCourse = loginAsStudentInCourse
  },
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getEnrolledStudents()
  },
  methods: {
    initUnenrollStudent (student) {
      this.studentToUnenroll = student
      console.log(student)
      this.studentId = student.id
      this.sectionId = student.section_id
      this.$bvModal.show('modal-unenroll-student')
    },
    cancelUnenrollStudent () {
      this.$bvModal.hide('modal-unenroll-student')
    },
    async submitUnenrollStudent () {
      try {
        const { data } = await this.unenrollStudentForm.delete(`/api/enrollments/${this.sectionId}/${this.studentId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getEnrolledStudents()
        }
        this.$bvModal.hide('modal-unenroll-student')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        }
      }
      this.unenrollStudentForm.confirmation = ''
    },
    cancelMoveStudent () {
      this.$bvModal.hide('modal-move-student')
    },
    async submitMoveStudent () {
      try {
        const { data } = await this.moveStudentForm.patch(`/api/enrollments/${this.courseId}/${this.studentId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getEnrolledStudents()
        }
        this.$bvModal.hide('modal-move-student')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        }
      }
    },
    initMoveStudent (student) {
      this.moveStudentForm.section_id = 0
      this.studentToMove = student
      this.studentId = student.id
      this.studentSectionOptions = [{ text: 'Please choose a section', value: 0 }]
      for (let i = 0; i < this.sectionOptions.length; i++) {
        let section = this.sectionOptions[i]
        if (section.value !== student.section_id) {
          this.studentSectionOptions.push(section)
        }
      }
      this.$bvModal.show('modal-move-student')
    },
    async getEnrolledStudents () {
      try {
        const { data } = await axios.get(
          `/api/enrollments/${this.courseId}/details`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.enrollments = data.enrollments
        this.sectionOptions = data.sections
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
