<template>
  <div>
    <b-modal
      id="modal-h5p-questions-in-course-with-anonymous-users-warning"
      title="H5P Assessments With Anonymous Users Warning"
    >
      <b-alert :show="true" variant="danger" class="font-weight-bold">
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
      <b-alert :show="true" variant="danger" class="font-weight-bold">
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
      <b-alert :show="true" variant="danger" class="font-weight-bold">
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
      <b-alert :show="true" variant="danger" class="font-weight-bold">
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
    <b-tooltip target="textbook_url_tooltip"
               delay="250"
               triggers="hover focus"
    >
      If you are planning on serving ADAPT through a textbook, this would be an optional link to the textbook.
    </b-tooltip>
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
    <b-tooltip target="anonymous_users_tooltip">
      If you allow anonymous users, then anybody can view all assessments in your course. However, submissions
      are not saved and answers are not provided.
    </b-tooltip>
    <b-tooltip target="whitelisted_domains_tooltip">
      <p>
        The whitelisted domains determine acceptable emails for students in your course. For example, if you just want to accept
        mySchool.edu
        email addresses, you can whitelist mySchool.edu.
      </p>
      <p>
        Then, only students using email addresses that contain mySchool.edu will be able to enroll.
      </p>
    </b-tooltip>
    <b-tooltip target="summative_formative_tooltip"
               delay="250"
               triggers="hover focus"
    >
      Traditional courses consist of summative assignments which can only be accessed by enrolled students. Instructors
      can optionally incorporate formative
      assignments for additional ungraded practice.
    </b-tooltip>
    <b-tooltip target="formative_tooltip"
               delay="250"
               triggers="hover focus"
    >
      Formative courses consist solely of formative assignments.
    </b-tooltip>
    <b-tooltip target="school_tooltip"
               delay="250"
               triggers="hover focus"
    >
      ADAPT keeps a comprehensive list of colleges and universities, using the school's full name. So, to find UC-Davis,
      you
      can start typing University of California-Los Angeles. In general, any word within your school's name will lead
      you to your school. If you still can't
      find it, then please contact us.
    </b-tooltip>
    <RequiredText/>
    <b-form ref="form">
      <b-form-group
        v-if="user.role === 2"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="school"
      >
        <template v-slot:label>
          School

          <QuestionCircleTooltip :id="'school_tooltip'"/>
        </template>
        <v-select id="school"
                  v-model="form.school"
                  placeholder="Choose a school"
                  :options="schools"
                  class="mb-2"
                  :class="{ 'is-invalid': form.errors.has('school') }"
                  @keydown="form.errors.clear('school')"
                  @input="checkIfLTI($event)"
        />
        <has-error :form="form" field="school"/>
      </b-form-group>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="name"
      >
        <template v-slot:label>
          Course Name*
        </template>
        <b-form-input
          id="name"
          v-model="form.name"
          required
          type="text"
          :class="{ 'is-invalid': form.errors.has('name') }"
          @keydown="form.errors.clear('name')"
        />
        <has-error :form="form" field="name"/>
      </b-form-group>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="public_description"
      >
        <template v-slot:label>
          Public Description
          <QuestionCircleTooltip :id="'public-description-tooltip'"/>
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
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="private_description"
      >
        <template v-slot:label>
          Private Description
          <QuestionCircleTooltip :id="'private-description-tooltip'"/>
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
      <b-form-group
        v-if="user.role === 2"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="textbook_url"
      >
        <template v-slot:label>
          Textbook URL
          <QuestionCircleTooltip id="textbook_url_tooltip"/>
        </template>
        <b-form-textarea
          id="textbook_url"
          v-model="form.textbook_url"
          :class="{ 'is-invalid': form.errors.has('textbook_url') }"
          rows="2"
          max-rows="2"
          @keydown="form.errors.clear('textbook_url')"
        />
        <has-error :form="form" field="textbook_url"/>
      </b-form-group>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="modality"
        label="Modality*"
      >
        <b-form-radio-group id="formative"
                            v-model="modality"
                            stacked
                            required
                            :disabled="course && course.is_beta_course"
                            @change="updateModality($event)"
        >
          <b-form-radio name="modality" value="summative_formative">
            Traditional
            <QuestionCircleTooltip id="summative_formative_tooltip"/>
          </b-form-radio>
          <b-form-radio name="modality" value="formative">
            Formative only
            <QuestionCircleTooltip id="formative_tooltip"/>
          </b-form-radio>
          <b-form-radio name="modality" value="anonymous_users">
            Anonymous Users
            <QuestionCircleTooltip :id="'anonymous_users_tooltip'"/>
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-if="user.role === 2 && !+form.formative"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="whitelisted_domains"
      >
        <template v-slot:label>
          Whitelisted Domains*
          <QuestionCircleTooltip id="whitelisted_domains_tooltip"/>
        </template>
        <b-form-row class="mt-2">
          <b-form-input
            id="tags"
            v-model="whitelistedDomain"
            style="width:200px"
            type="text"
            :class="{ 'is-invalid': form.errors.has('whitelisted_domains') }"
            required
            class="mr-2"
            size="sm"
            @blur="addWhitelistedDomain(whitelistedDomain)"
          />
          <b-button variant="outline-primary" size="sm" @click="addWhitelistedDomain(whitelistedDomain)">
            Add whitelisted domain
          </b-button>
          <has-error :form="form" field="whitelisted_domains"/>
        </b-form-row>
        <div class="d-flex flex-row">
          <span v-for="chosenWhitelistedDomain in form.whitelisted_domains" :key="chosenWhitelistedDomain"
                class="mt-2"
          >
            <b-button size="sm"
                      variant="secondary"
                      class="mr-2"
                      style="line-height:.8"
                      @click="removeWhitelistedDomain(chosenWhitelistedDomain)"
            ><span v-html="chosenWhitelistedDomain"/> x</b-button>
          </span>
        </div>
      </b-form-group>
      <div v-if="parseInt(form.anonymous_users) === 1">
        <b-alert type="info" :show="!(course && course.id)">
          Once your course is created, you can visit the Course properties to obtain a special link for Anonymous Users
          to access your course.
        </b-alert>
        <b-alert type="info" :show="course && course.id" class="font-weight-bold text-center">
          <p>Your anonymous users will be able to enter your course using the following url:</p>
          <p>{{ getAnonymousUserEntryUrl() }}</p>
          <p>
            If you would like to view this course as an Anonymous User, please log out of this account first before
            visiting the URL.
          </p>
        </b-alert>
      </div>
      <div v-show="!+form.formative">
        <div v-if="'section' in form">
          <b-form-group
            v-if="user.role === 2"
            label-cols-sm="4"
            label-cols-lg="3"
            label-for="section"
          >
            <template v-slot:label>
              Section*
              <QuestionCircleTooltip :id="'section-name-tooltip'"/>
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
              required
              @keydown="form.errors.clear('section')"
            />
            <has-error :form="form" field="section"/>
          </b-form-group>
          <b-form-group
            v-if="user.role === 2"
            label-cols-sm="4"
            label-cols-lg="3"
            label-for="crn"
          >
            <template v-slot:label>
              CRN*
              <QuestionCircleTooltip :id="'crn-tooltip'"/>
              <b-tooltip target="crn-tooltip"
                         triggers="hover focus"
                         delay="250"
              >
                The Course Reference Number is the number that identifies a specific section of a course being
                offered.
              </b-tooltip>
            </template>
            <b-form-input
              id="crn"
              v-model="form.crn"
              type="text"
              placeholder=""
              required
              :class="{ 'is-invalid': form.errors.has('crn') }"
              @keydown="form.errors.clear('crn')"
            />
            <has-error :form="form" field="crn"/>
          </b-form-group>
        </div>
        <b-form-group
          v-if="user.role === 2"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="term"
        >
          <template v-slot:label>
            Term*
            <QuestionCircleTooltip :id="'term-tooltip'"/>
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
            required
            type="text"
            :class="{ 'is-invalid': form.errors.has('term') }"
            @keydown="form.errors.clear('term')"
          />
          <has-error :form="form" field="term"/>
        </b-form-group>
        <b-form-group
          v-if="user.role === 2"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="start_date"
        >
          <template v-slot:label>
            Start Date*
          </template>
          <b-form-datepicker
            id="start_date"
            v-model="form.start_date"
            tabindex="0"
            :min="min"
            :class="{ 'is-invalid': form.errors.has('start_date') }"
            required
            @shown="form.errors.clear('start_date')"
          />
          <has-error :form="form" field="start_date"/>
        </b-form-group>

        <b-form-group
          v-if="user.role === 2"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="end_date"
        >
          <template v-slot:label>
            End Date*
          </template>
          <b-form-datepicker
            id="end_date"
            v-model="form.end_date"
            required
            tabindex="0"
            :min="min"
            class="mb-2"
            :class="{ 'is-invalid': form.errors.has('end_date') }"
            @click="form.errors.clear('end_date')"
            @shown="form.errors.clear('end_date')"
          />
          <has-error :form="form" field="end_date"/>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="public"
        >
          <template v-slot:label>
            Public*
            <QuestionCircleTooltip :id="'public_tooltip'"/>
          </template>
          <b-form-radio-group id="public"
                              v-model="form.public"
                              aria-label="Public*"
                              required
                              stacked
                              name="public"
          >
            <b-form-radio value="1">
              Yes
            </b-form-radio>
            <b-form-radio value="0">
              No
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <b-form-group
          v-if="user.role === 2"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="alpha"
        >
          <template v-slot:label>
            Alpha*
            <QuestionCircleTooltip :id="'alpha_course_tooltip'"/>
          </template>
          <b-form-radio-group id="alpha"
                              v-model="form.alpha"
                              required
                              stacked
                              @change="validateCanChange"
          >
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
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="untether_beta_course"
        >
          <template v-slot:label>
            Untether Beta Course*
            <QuestionCircleTooltip :id="'untether_beta_course_tooltip'"/>
          </template>
          <b-form-radio-group
            v-model="form.untether_beta_course"
            stacked
            required
          >
            <span @click="showUntetherBetaCourseWarning"><b-form-radio name="untether_beta_course" value="1">
              Yes
            </b-form-radio></span>

            <b-form-radio name="untether_beta_course" value="0">
              No
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <b-form-group
          v-if="user.role === 2"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="lms"
        >
          <template v-slot:label>
            LMS*
            <QuestionCircleTooltip :id="'lms_course_tooltip'"/>
          </template>
          <span v-show="!ltiIsEnabled">The LMS at <span class="font-weight-bold">{{ form.school }}</span> has not been
            configured to be used with ADAPT.  If you would like to integrate ADAPT with your LMS, please have your LMS Admin reach out to us via the contact form.</span>
          <b-form-radio-group v-if="ltiIsEnabled"
                              v-model="form.lms"
                              required
                              stacked
          >
            <b-form-radio name="lms" value="1">
              Yes
            </b-form-radio>
            <b-form-radio name="lms" value="0">
              No
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>
    </b-form>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'
import { fixDatePicker } from '~/helpers/accessibility/FixDatePicker'
import { fixRequired } from '~/helpers/accessibility/FixRequired'
import 'vue-select/dist/vue-select.css'

const now = new Date()
export default {
  name: 'CourseForm',
  props: {
    form: { type: Object, default: null },
    course: { type: Object, default: null }
  },
  data: () => ({
    whitelistedDomain: '',
    modality: 'summative_formative',
    ltiIsEnabled: false,
    ltiSchools: [],
    schools: [],
    min: new Date(now.getFullYear(), now.getMonth(), now.getDate())
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    fixRequired(this)
    fixDatePicker('start_date', 'start date')
    fixDatePicker('end_date', 'end date')
    let startDate = document.getElementById('start_date')
    startDate.style.opacity = '0'
    startDate.style.width = '0'
    startDate.style.padding = '5px'
    let endDate = document.getElementById('end_date')
    endDate.style.opacity = '0'
    endDate.style.width = '0'
    endDate.style.padding = '5px'
    this.getSchools()
    this.getLTISchools()
    console.log(this.course)
    if (this.course) {
      this.setModality(this.form)
    }
  },
  methods: {
    removeWhitelistedDomain (chosenWhitelistedDomain) {
      this.form.whitelisted_domains = this.form.whitelisted_domains.filter(whitelistedDomain => whitelistedDomain !== chosenWhitelistedDomain)
      this.$forceUpdate()
      this.$noty.info(`${chosenWhitelistedDomain} has been removed.`)
    },
    addWhitelistedDomain (whitelistedDomain) {
      whitelistedDomain = whitelistedDomain.replace(/(.*)@/, '')
      if (whitelistedDomain) {
        if (!this.form.whitelisted_domains.includes(whitelistedDomain)) {
          this.form.whitelisted_domains.push(whitelistedDomain)
        } else {
          this.$noty.info(`${whitelistedDomain} is already on your list of whitelisted domains.`)
        }
      }
      this.whitelistedDomain = ''
    },
    setModality (form) {
      if (form.anonymous_users) {
        this.modality = 'anonymous_users'
      } else if (form.formative) {
        this.modality = 'formative'
      } else {
        this.modality = 'summative_formative'
      }
    },
    updateModality (modality) {
      if (this.course && !this.course.owns_all_questions && modality === 'formative' && !this.form.owns_all_questions) {
        this.$noty.info('You do not own all questions for every assignment in this course so you can\'t change it to a formative course.')

        this.$nextTick(() => {
          if (this.form.anonymous_users) {
            this.modality = 'anonymous_users'
          } else {
            this.modality = 'summative_formative'
          }
        })
        return
      }

      switch (modality) {
        case ('summative_formative'):
          this.form.formative = 0
          this.form.anonymous_users = 0
          break
        case ('formative'):
          this.form.formative = 1
          this.form.anonymous_users = 0
          break
        case ('anonymous_users'):
          this.form.formative = 0
          this.form.anonymous_users = 1
      }
    },
    checkIfLTI (school) {
      this.ltiIsEnabled = this.ltiSchools.includes(school)
    },
    async getLTISchools () {
      try {
        const { data } = await axios.get('/api/lti-school')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.ltiSchools = data.lti_schools
        this.ltiIsEnabled = this.ltiSchools.includes(this.form.school)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
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
