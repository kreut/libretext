<template>
  <div>
    <b-modal
      id="modal-h5p-questions-in-course-with-anonymous-users-warning"
      title="H5P Assessments With Anonymous Users Warning"
    >
      <b-alert :show="true" variant="danger" class="font-weight-bold font-italic">
        You are allowing anonymous users in a course with H5P assessments. Due to the nature of this technology,
        users will be able to view the solutions to these types of assessments.
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary"
                  @click="$bvModal.hide('modal-h5p-questions-in-course-with-anonymous-users-warning')"
        >
          I understand the consequences
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-alpha-course-anonymous-users-warning"
      title="Alpha Course With Anonymous Users Warning"
    >
      <b-alert :show="true" variant="danger" class="font-weight-bold font-italic">
        By allowing anonymous users in an alpha course, this course will essentially become an open course for
        all associated beta courses since anyone will be able to view this course's assignments and assessments.
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-alpha-course-anonymous-users-warning')">
          I understand the consequences
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-beta-course-anonymous-users-warning"
      title="Beta Course With Anonymous Users Warning"
    >
      <b-alert :show="true" variant="danger" class="font-weight-bold font-italic">
        By allowing anonymous users in a beta course, this course will essentially become an open course for
        all other associated beta courses and the original alpha course since anyone will be able to view this course's
        assignments and assessments.
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-beta-course-anonymous-users-warning')">
          I understand the consequences
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-untether-beta-course-warning"
      ref="untetherBetaCourseWarning"
      title="Untether Beta Course Warning"
    >
      <b-alert :show="true" variant="danger" class="font-weight-bold font-italic">
        <p>
          You are choosing to untether this Beta course from its Alpha course. Changes in the Alpha course will no
          longer
          be reflected in this course. In addition, if your course is served through a Libretext, your students will
          no longer be able to access their assignments through your Libretext.
        </p>
        <p>If you choose this option and submit the form, you will not be able to re-tether the course.</p>
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-untether-beta-course-warning')">
          I understand the consequences
        </b-button>
      </template>
    </b-modal>
    <b-tooltip target="public-description-tooltip"
               delay="250"
               triggers="hover focus"
    >
      An optional description for the course. This description will be viewable by your students.
    </b-tooltip>
    <b-tooltip target="untether_beta_course_tooltip"
               delay="250"
               triggers="hover focus"
    >
      If you would like to regain complete control over this Beta course, you can untether it. By untethering the
      course,
      you will be able to add/remove any assessments. Please note that if you are using your course in a Libretext and
      untether it from the associated Alpha course, your students will no longer be able to access those assessments in
      the Libretexdt.
    </b-tooltip>
    <b-tooltip target="alpha_course_tooltip"
               delay="250"
               triggers="hover focus"
    >
      If you designate this course as an Alpha course, other instructors will be able to create Beta courses which
      are tethered to the Alpha course. Assignments in Alpha courses will then be replicated in the associated Beta
      courses.
      Because of the tethering feature, Alpha courses cannot be deleted unless all associated Beta courses are deleted.
    </b-tooltip>
    <b-tooltip target="lms_course_tooltip"
               delay="250"
               triggers="hover focus"
    >
      If you would like to serve your assignments through an LMS, we'll let your LMS handle assigning students and the
      course gradebook. Currently we support Canvas but will be expanding per instructor request.
    </b-tooltip>
    <b-tooltip target="public_tooltip"
               delay="250"
               triggers="hover focus"
    >
      Public courses can be imported by other instructors; non-public can only be imported by you. Note that student
      grades will never be made public nor copied from a course.
    </b-tooltip>
    <b-tooltip target="anonymous_users_tooltip"
               delay="250"
               triggers="hover focus"
    >
      If you allow anonymous users, then anybody can view all assessments in your course. Submissions from
      anonymous users won't be saved.
    </b-tooltip>
    <b-tooltip target="school_tooltip"
               delay="250"
               triggers="hover focus"
    >
      Adapt keeps a comprehensive list of colleges and universities, using the school's full name. So, to find UC-Davis,
      you
      can start typing University of California-Los Angeles. In general, any word within your school's name will lead
      you to your school. If you still can't
      find it, then please contact us.
    </b-tooltip>
    <b-form ref="form">
      <b-form-group
        id="school"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="school"
      >
        <template slot="label">
          School
          <a id="school_tooltip" href="#">
            <b-icon class="text-muted" icon="question-circle"/>
          </a>
        </template>
        <vue-bootstrap-typeahead
          ref="schoolTypeAhead"
          v-model="form.school"
          :data="schools"
          placeholder="Not Specified"
          :class="{ 'is-invalid': form.errors.has('school') }"
          @keydown="form.errors.clear('school')"
        />
        <has-error :form="form" field="school"/>
      </b-form-group>
      <b-form-group
        id="name"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Name"
        label-for="name"
      >
        <b-form-input
          id="name"
          v-model="form.name"
          type="text"
          :class="{ 'is-invalid': form.errors.has('name') }"
          @keydown="form.errors.clear('name')"
        />
        <has-error :form="form" field="name"/>
      </b-form-group>
      <b-form-group
        id="public_description"
        label-cols-sm="4"
        label-cols-lg="3"
      >
        <template slot="label">
          Public Description
          <a id="public-description-tooltip" href="#">
            <b-icon
              class="text-muted"
              icon="question-circle"
            />
          </a>
        </template>
        <b-form-textarea
          id="public_description"
          v-model="form.public_description"
          style="margin-bottom: 23px"
          rows="2"
          max-rows="2"
        />
      </b-form-group>
      <b-form-group
        id="private_description"
        label-cols-sm="4"
        label-cols-lg="3"
      >
        <template slot="label">
          Private Description
          <a id="private-description-tooltip"
             href="#"
             class="text-muted"
          >
            <b-icon
              class="text-muted"
              icon="question-circle"
            />
          </a>
          <b-tooltip target="private-description-tooltip"
                     triggers="hover focus"
                     delay="250"
          >
            An optional description for the course. This description will only be viewable by you.
          </b-tooltip>
        </template>
        <b-form-textarea
          id="private_description"
          v-model="form.private_description"
          style="margin-bottom: 23px"
          rows="2"
          max-rows="2"
        />
      </b-form-group>
      <div v-if="'section' in form">
        <b-form-group
          id="section"
          label-cols-sm="4"
          label-cols-lg="3"
        >
          <template slot="label">
            Section
            <a id="section-name-tooltip"
               href="#"
            >
              <b-icon
                class="text-muted"
                icon="question-circle"
              />
            </a>
            <b-tooltip target="section-name-tooltip"
                       triggers="hover focus"
                       delay="250"
            >
              A descriptive name for the section. You can add more sections after the course is created.
            </b-tooltip>
          </template>
          <b-form-input
            id="section"
            v-model="form.section"
            type="text"
            :class="{ 'is-invalid': form.errors.has('section') }"
            @keydown="form.errors.clear('section')"
          />
          <has-error :form="form" field="section"/>
        </b-form-group>
        <b-form-group
          id="crn"
          label-cols-sm="4"
          label-cols-lg="3"
        >
          <template slot="label">
            CRN
            <a id="crn-tooltip"
               href="#"
            >
              <b-icon
                class="text-muted"
                icon="question-circle"
              />
            </a>
            <b-tooltip target="crn-tooltip"
                       triggers="hover focus"
                       delay="250"
            >
              The Course Reference Number is the number that identifies a specific section of a course being offered.
            </b-tooltip>
          </template>
          <b-form-input
            id="crn"
            v-model="form.crn"
            type="text"
            placeholder=""
            :class="{ 'is-invalid': form.errors.has('crn') }"
            @keydown="form.errors.clear('crn')"
          />
          <has-error :form="form" field="crn"/>
        </b-form-group>
      </div>
      <b-form-group
        id="term"
        label-cols-sm="4"
        label-cols-lg="3"
      >
        <template slot="label">
          Term
          <a id="term-tooltip"
             href="#"
          >
            <b-icon
              class="text-muted"
              icon="question-circle"
            />
          </a>
          <b-tooltip target="term-tooltip"
                     triggers="hover focus"
                     delay="250"
          >
            The form of this field will depend on your school. As one example, it might be 202103 to represent 3rd
            Quarter of 2021 "year-quarter" such as 2021-03.
          </b-tooltip>
        </template>
        <b-form-input
          id="term"
          v-model="form.term"
          type="text"
          :class="{ 'is-invalid': form.errors.has('term') }"
          @keydown="form.errors.clear('term')"
        />
        <has-error :form="form" field="term"/>
      </b-form-group>
      <b-form-group
        id="start_date"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Start Date"
        label-for="Start Date"
      >
        <b-form-datepicker
          id="start_date"
          v-model="form.start_date"
          :min="min"
          :class="{ 'is-invalid': form.errors.has('start_date') }"
          @shown="form.errors.clear('start_date')"
        />
        <has-error :form="form" field="start_date"/>
      </b-form-group>

      <b-form-group
        id="end_date"
        label-cols-sm="4"
        label-cols-lg="3"
        label="End Date"
        label-for="End Date"
      >
        <b-form-datepicker
          id="end_date"
          v-model="form.end_date"
          :min="min"
          class="mb-2"
          :class="{ 'is-invalid': form.errors.has('end_date') }"
          @click="form.errors.clear('end_date')"
          @shown="form.errors.clear('end_date')"
        />
        <has-error :form="form" field="end_date"/>
      </b-form-group>
      <b-form-group
        id="public"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="Public"
      >
        <template slot="label">
          Public
          <a id="public_tooltip"
             href="#"
          >
            <b-icon class="text-muted" icon="question-circle"/>
          </a>
        </template>
        <b-form-radio-group id="public" v-model="form.public" stacked>
          <b-form-radio name="public" value="1">
            Yes
          </b-form-radio>

          <b-form-radio name="public" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        id="anonymous_users"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="Anonymous Users"
      >
        <template slot="label">
          Anonymous Users
          <a id="anonymous_users_tooltip" href="#">
            <b-icon
              class="text-muted"
              icon="question-circle"
            />
          </a>
        </template>
        <b-form-radio-group v-model="form.anonymous_users" stacked @change="showAnonymousUsersWarning">
          <b-form-radio name="anonymous_users" value="1">
            Yes
          </b-form-radio>

          <b-form-radio name="anonymous_users" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <span v-if="parseInt(form.anonymous_users) === 1">
        <b-alert type="info" :show="!(course && course.id)">
          Once your course is created, you can visit the Course properties to obtain a special link for Anonymous Users to access your course.
        </b-alert>
        <b-alert type="info" :show="course && course.id" class="font-weight-bold text-center">
          <p>Your anonymous users will be able to enter your course using the following url:</p>
          <p>{{ getAnonymousUserEntryUrl() }}</p>
          <p>If you would like to view this course as an Anonymous User, please log out of this account first before visiting the URL.</p>
        </b-alert>
      </span>
      <b-form-group
        id="alpha"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="alpha"
      >
        <template slot="label">
          Alpha
          <a id="alpha_course_tooltip" href="#">
            <b-icon class="text-muted" icon="question-circle"/>
          </a>
        </template>
        <b-form-radio-group v-model="form.alpha" stacked @change="validateCanChange">
          <b-form-radio name="alpha" value="1">
            Yes
          </b-form-radio>

          <b-form-radio name="alpha" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-show="course && course.is_beta_course"
        id="untether_beta_course"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="untether_beta_course"
      >
        <template slot="label">
          Untether Beta Course
          <span id="untether_beta_course_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
        </template>
        <b-form-radio-group v-model="form.untether_beta_course" stacked>
          <span @click="showUntetherBetaCourseWarning"><b-form-radio name="untether_beta_course" value="1">
            Yes
          </b-form-radio></span>

          <b-form-radio name="untether_beta_course" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-show="['adapt@libretexts.org','hagnew@libretexts.org','blindsh@ksu.edu'].includes(user.email)"
        id="lms"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="lms"
      >
        <template slot="label">
          LMS
          <a id="lms_course_tooltip" href="#">
            <b-icon class="text-muted" icon="question-circle"/>
          </a>
        </template>
        <b-form-radio-group v-model="form.lms" stacked>
          <b-form-radio name="lms" value="1">
            Yes
          </b-form-radio>

          <b-form-radio name="lms" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
    </b-form>
  </div>
</template>

<script>
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import axios from 'axios'
import { mapGetters } from 'vuex'

const now = new Date()
export default {
  name: 'CourseForm',
  components: {
    VueBootstrapTypeahead
  },
  props: {
    form: { type: Object, default: null },
    course: { type: Object, default: null }
  },
  data: () => ({
    schools: [],
    min: new Date(now.getFullYear(), now.getMonth(), now.getDate())
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (this.form.school) {
      this.$refs.schoolTypeAhead.inputValue = this.form.school
    }
    this.getSchools()
    console.log(this.course)
  },
  methods: {
    getAnonymousUserEntryUrl () {
      if (!this.course) {
        return
      }
      return window.location.origin + `/courses/${this.course.id}/anonymous`
    },
    async showAnonymousUsersWarning () {
      if (parseInt(this.form.anonymous_users) === 0) {
        if (await this.courseHasH5PAssessments()) {
          this.$bvModal.show('modal-h5p-questions-in-course-with-anonymous-users-warning')
        }
        if (parseInt(this.form.alpha) === 1) {
          this.$bvModal.show('modal-alpha-course-anonymous-users-warning')
        }
        if (this.course && this.course.is_beta_course) {
          this.$bvModal.show('modal-beta-course-anonymous-users-warning')
        }
      }
    },
    async courseHasH5PAssessments () {
      if (this.course && this.course.id) {
        try {
          const { data } = await axios.get(`/api/courses/${this.course.id}/has-h5p-questions`)
          if (data.type === 'error') {
            this.$noty.error(data.message)
            return false
          }
          return data.h5p_questions_exist
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
    },
    showUntetherBetaCourseWarning () {
      if (parseInt(this.form.untether_beta_course) === 0) {
        this.$bvModal.show('modal-untether-beta-course-warning')
      }
    },
    async validateCanChange () {
      if (!(this.course && this.course.id)) {
        return
      }
      let valid = true
      let currentSelection = this.form.alpha
      if (this.course.alpha && this.course.beta_courses_info.length) {
        valid = false
        this.$noty.info('You can\'t change this option since there are Beta courses associated with this Alpha course.')
      }
      if (this.course.is_beta_course) {
        valid = false
        this.$noty.info('You can\'t change this option since this is already a Beta course.')
      }
      if (!valid) {
        this.$nextTick(() => {
          this.form.alpha = currentSelection
        })
      }
    },
    async getSchools () {
      try {
        const { data } = await axios.get(`/api/schools`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.schools = data.schools
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
