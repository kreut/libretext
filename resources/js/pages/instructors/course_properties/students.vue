<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-unenroll-student"/>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-move-student"/>
    <b-modal
      id="modal-unenroll-student"
      ref="modal"
      title="Unenroll Student"
      :no-close-on-esc="true"
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
            v-model="unenrollStudentForm.confirmation"
            class="col-6"
            required
            placeholder="Please enter the student's full name."
            type="text"
            :class="{ 'is-invalid': unenrollStudentForm.errors.has('confirmation') }"
            @keydown="unenrollStudentForm.errors.clear('confirmation')"
          />
          <has-error :form="unenrollStudentForm" field="confirmation"/>
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
      :no-close-on-esc="true"
    >
      <b-alert show variant="info">
        <span class="font-weight-bold">The student's  submissions from the originating section will be removed
          if the associated assignment doesn't exist in the new section.</span>
      </b-alert>
      <p>
        <span class="font-italic">{{ studentToMove.name }} is currently enrolled in {{ studentToMove.section }}.</span>
      </p>
      <RequiredText :plural="false"/>
      <b-form ref="form">
        <b-form-group
          label-cols-sm="5"
          label-cols-lg="4"
          label="Move student"
          label-for="move_student"
        >
          <template slot="label">
            Move Student*
          </template>
          <div class="mb-2 mr-2">
            <b-form-select
              id="move_student"
              v-model="moveStudentForm.section_id"
              required
              :options="studentSectionOptions"
              :class="{ 'is-invalid': moveStudentForm.errors.has('section_id') }"
              @keydown="moveStudentForm.errors.clear('section_id')"
            />
            <has-error :form="moveStudentForm" field="section_id"/>
          </div>
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <span v-if="processingMoveStudent">
          <b-spinner small type="grow"/>
          Processing...
        </span>
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
          :disabled="processingMoveStudent"
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
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Students</h2>">
          <b-card-text>
            <div v-if="enrollments.length">
              <b-table striped
                       hover
                       aria-label="Students"
                       :fields="fields"
                       :items="enrollments"
              >
                <template v-slot:cell(name)="data">
                  <a href="#" @click="loginAsStudentInCourse(data.item.id)">{{ data.item.name }}</a>
                </template>
                <template v-slot:cell(email)="data">
                  <span :id="`email-${data.item.id}}`">{{ data.item.email }} </span> <a
                  href="#"
                  class="pr-1"
                  :aria-label="`Copy email for ${data.item.name}`"
                  @click="doCopy(`email-${data.item.id}}`)"
                >
                  <font-awesome-icon
                    :icon="copyIcon"
                  />
                </a>
                </template>
                <template v-slot:cell(actions)="data">
                  <b-tooltip :target="getTooltipTarget('moveStudent',data.item.id)"
                             delay="500"
                             triggers="hover focus"
                  >
                    Move student to a different section
                  </b-tooltip>
                  <a v-show="sectionOptions.length>1"
                     :id="getTooltipTarget('moveStudent',data.item.id)"
                     href=""
                     @click.prevent="initMoveStudent(data.item)"
                  >
                    <b-icon icon="truck"
                            class="text-muted"
                            :aria-label="`Move ${data.item.name} to a different section`"
                    />
                  </a>
                  <b-tooltip :target="getTooltipTarget('unEnrollStudent',data.item.id)"
                             delay="500"
                             triggers="hover focus"
                  >
                    Unenroll student
                  </b-tooltip>
                  <a :id="getTooltipTarget('unEnrollStudent',data.item.id)"
                     href=""
                     @click.prevent="initUnenrollStudent(data.item)"
                  >
                    <b-icon icon="trash" class="text-muted" :aria-label="`Unenroll ${data.item.name}`"/>
                  </a>
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
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { loginAsStudentInCourse } from '~/helpers/LoginAsStudentInCourse'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import AllFormErrors from '~/components/AllFormErrors'

export default {
  middleware: 'auth',
  components: {
    Loading,
    FontAwesomeIcon,
    AllFormErrors
  },
  data: () => ({
    allFormErrors: [],
    processingMoveStudent: false,
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
    fields: [],
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
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
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
        } else {
          this.allFormErrors = this.unenrollStudentForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-unenroll-student')
        }
      }
      this.unenrollStudentForm.confirmation = ''
    },
    cancelMoveStudent () {
      this.$bvModal.hide('modal-move-student')
    },
    async submitMoveStudent () {
      this.processingMoveStudent = true
      try {
        const { data } = await this.moveStudentForm.patch(`/api/enrollments/${this.courseId}/${this.studentId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getEnrolledStudents()
        }
        this.$bvModal.hide('modal-move-student')
        this.processingMoveStudent = false
      } catch (error) {
        this.processingMoveStudent = false
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          this.allFormErrors = this.moveStudentForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-move-student')
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
        this.fields = [
          {
            key: 'name',
            isRowHeader: true
          },
          {
            key: 'student_id',
            label: 'Student ID'
          },
          'email',
          'enrollment_date',
          {
            key: 'section',
            label: 'Section'
          },
          'actions']
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
