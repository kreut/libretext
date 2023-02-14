<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-invite-graders'"/>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-edit-sections'"/>
    <b-modal
      id="modal-confirm-remove"
      ref="modal"
      title="Remove Grader"
    >
      <p>
        Are you sure you would like to remove this grader? Once removed, they will no longer be able to grade
        for you unless you invite them back.
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="cancelRemoveGrader"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="submitRemoveGrader"
        >
          Yes, remove this grader!
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-edit-sections"
      ref="modal"
      title="Edit Sections"

    >
      <b-form ref="form">
        Choose individual sections or <a href="#" @click="selectAllSections">select all</a>:
        <b-form-checkbox-group
          v-model="sectionsForm.selected_sections"
          :options="sectionOptions"
          :class="{ 'is-invalid': sectionsForm.errors.has('selected_sections') }"
          name="sections"
          @keydown="sectionsForm.errors.clear('selected_sections')"
        />
        <has-error :form="sectionsForm" field="selected_sections"/>
      </b-form>
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitEditSections"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-invite-grader"
      ref="modal"
      title="Invite Grader"

    >
      <RequiredText/>
      <b-form ref="form">
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label="Email*"
          label-for="grader_email"
        >
          <b-form-input
            id="grader_email"
            v-model="graderForm.email"
            required
            placeholder="Email Address"
            type="text"
            :class="{ 'is-invalid': graderForm.errors.has('email') }"
            @keydown="graderForm.errors.clear('email')"
          />
          <has-error :form="graderForm" field="email"/>
        </b-form-group>
        Choose individual sections or <a href="#" @click="selectAllSections">select all</a>:
        <b-form-checkbox-group
          v-model="graderForm.selected_sections"
          :options="sectionOptions"
          :class="{ 'is-invalid': graderForm.errors.has('selected_sections') }"
          name="sections"
          @keydown="graderForm.errors.clear('selected_sections')"
        />
        <has-error :form="graderForm" field="selected_sections"/>
      </b-form>
      <template #modal-footer>
        <span v-if="sendingEmail">
          <b-spinner small type="grow"/>
          Sending Email..
        </span>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitInviteGrader"
        >
          Submit
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
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Graders</h2>">
          <b-card-text>
            <div v-if="user.email !== 'commons@libretexts.org'">
              <b-container>
                <b-row align-h="end">
                  <b-button class="mb-2" variant="primary" size="sm" @click="initInviteGrader()">
                    Invite Grader
                  </b-button>
                </b-row>
              </b-container>
              <div v-if="course.graders.length">
                <b-form-group
                  id="head_grader"
                  label-cols-sm="3"
                  label-cols-lg="2"
                  label-for="Head Grader"
                >
                  <template v-slot:label>
                    Head Grader
                    <QuestionCircleTooltip :id="'head-grader-tooltip'"/>
                    <b-tooltip target="head-grader-tooltip"
                               triggers="hover focus"
                               delay="500"
                    >
                      Optionally choose a head grader. Head graders can be sent a summary of ungraded assignments by
                      visiting the Grading Notifications page.
                    </b-tooltip>
                  </template>
                  <b-form-row>
                    <b-col lg="6">
                      <b-form-select v-model="headGrader"
                                     title="Select a head grader"
                                     :options="graderOptions"
                                     @change="submitHeadGrader()"
                      />
                    </b-col>
                  </b-form-row>
                </b-form-group>
                <b-table striped hover
                         aria-label="Graders"
                         :fields="fields"
                         :items="graders"
                >
                  <template v-slot:cell(sections)="data">
                    {{ formatSections(data.item.sections) }}
                  </template>
                  <template v-slot:cell(actions)="data">
                    <a
                      href=""
                      aria-label="Edit Section"
                      @click.prevent="initEditSections(data.item)"
                    >
                      <b-icon icon="pencil"
                              class="text-muted"
                              :aria-label="`Edit sections for ${data.item.name}`"
                      />
                    </a>
                    <a
                      href=""
                      aria-label="Remove grader"
                      @click.prevent="initRemoveGrader(data.item.user_id)"
                    >
                      <b-icon icon="trash"
                              class="text-muted"
                              :aria-label="`Remove ${data.item.name} as a grader`"
                      />
                    </a>
                  </template>
                </b-table>
              </div>
              <div v-show=" !course.graders.length">
                <b-alert show variant="info">
                  <span class="font-weight-bold">You currently have no graders associated with this course.</span>
                </b-alert>
              </div>
            </div>
            <div v-else>
              <b-alert :show="true" variant="info">
                <span class="font-weight-bold">You cannot invite graders to courses in the Commons.</span>
              </b-alert>
            </div>
          </b-card-text>
        </b-card>
        <b-card v-if="graders.length"
                header="default"
                class="mt-3"
                header-html="<h2 class=&quot;h7&quot;>Override Grader Contact</h2>"
        >
          <b-card-text>
            <p>
              Students may have may have questions about their score or comments made by their grader.
            </p>
            <b-form-group
              id="head_grader"
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="grader_contact"
              label="Grader Contact"
            >
              <b-form-row>
                <b-col lg="6">
                  <b-form-select id="grader_contact"
                                 v-model="contactGraderOverride"
                                 title="Contact Override"
                                 :options="contactGraderOverrideOptions"
                                 @input="submitContactGraderOverride()"
                  />
                </b-col>
              </b-form-row>
            </b-form-group>

          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import AllFormErrors from '~/components/AllFormErrors'

export default {
  middleware: 'auth',
  components: {
    Loading,
    AllFormErrors
  },
  metaInfo () {
    return { title: 'Course Graders' }
  },
  data: () => ({
    contactGraderOverride: null,
    contactGraderOverrideOptions: [],
    allFormErrors: [],
    headGrader: null,
    graderOptions: [],
    graderToRemoveId: 0,
    sectionOptions: [],
    graderFormType: 'addGrader',
    fields: [
      {
        key: 'name',
        isRowHeader: true
      },
      'email',
      {
        key: 'sections',
        label: 'Section(s)'
      },
      'actions'

    ],
    sendingEmail: false,
    isLoading: true,
    graders: {},
    course: { graders: {} },
    grader_user_id: 0,
    sectionsForm: new Form({
      selected_sections: [],
      course_id: 0
    }),
    graderForm: new Form({
      email: '',
      selected_sections: [],
      course_id: 0
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getCourse(this.courseId)
  },
  methods: {
    async submitContactGraderOverride () {
      try {
        const { data } = await axios.patch(`/api/contact-grader-overrides/${this.courseId}`, { contact_grader_override: this.contactGraderOverride })
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitHeadGrader () {
      try {
        const { data } = this.headGrader !== null
          ? await axios.patch(`/api/head-graders/${this.courseId}/${this.headGrader}`)
          : await axios.delete(`/api/head-graders/${this.courseId}`)

        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    cancelRemoveGrader () {
      this.$bvModal.hide('modal-confirm-remove')
    },
    selectAllSections () {
      let allSections = []
      for (let i = 0; i < this.sectionOptions.length; i++) {
        allSections.push(this.sectionOptions[i].value)
      }

      this.graderForm.selected_sections = allSections
      this.sectionsForm.selected_sections = allSections
    },
    initInviteGrader () {
      this.graderForm.selectedSections = []
      this.graderForm.email = ''
      this.graderForm.errors.clear()
      this.$bvModal.show('modal-invite-grader')
    },
    initEditSections (graderInfo) {
      console.log(graderInfo)
      this.grader_user_id = graderInfo.user_id
      this.sectionsForm.selected_sections = Object.keys(graderInfo.sections)
      this.$bvModal.show('modal-edit-sections')
    },
    async submitEditSections (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        this.sectionsForm.course_id = this.courseId
        const { data } = await this.sectionsForm.patch(`/api/graders/${this.grader_user_id}`)
        if (data.type === 'success') {
          this.$noty.success(data.message)
          this.$bvModal.hide('modal-edit-sections')
          await this.getCourse(this.courseId)
          this.sendingEmail = false
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          this.allFormErrors = this.sectionsForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-edit-sections')
          this.$nextTick(() => {
            fixInvalid()
          })
        }
      }
    },
    formatSections (sections) {
      return Object.values(sections).join(', ')
    },
    async getCourse (courseId) {
      try {
        const { data } = await axios.get(`/api/courses/${courseId}`)
        this.course = data.course
        if (!this.sectionOptions.length) { // just do this on initializing
          for (let i = 0; i < this.course.sections.length; i++) {
            let section = this.course.sections[i]
            this.sectionOptions.push({ text: section.name, value: section.id })
          }
        }
        this.graders = this.course.graders
        this.graderOptions = [{ text: 'Please choose a head grader', value: null }]
        this.contactGraderOverrideOptions = [{ text: 'The section grader', value: null }, {
          text: 'Me',
          value: this.user.id
        }]
        this.contactGraderOverride = data.course.contact_grader_override
        for (let i = 0; i < this.graders.length; i++) {
          let grader = this.graders[i]
          let graderInfo = { text: grader.name, value: grader.user_id }
          this.graderOptions.push(graderInfo)
          this.contactGraderOverrideOptions.push(graderInfo)
        }
        this.isLoading = false
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initRemoveGrader (userId) {
      this.$bvModal.show('modal-confirm-remove')
      this.graderToRemoveId = userId
    },
    async submitRemoveGrader () {
      try {
        const { data } = await axios.delete(`/api/graders/${this.courseId}/${this.graderToRemoveId}`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-confirm-remove')
        if (data.type === 'error') {
          return false
        }
        // remove the grader
        await this.getCourse(this.courseId)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitInviteGrader (bvModalEvt) {
      bvModalEvt.preventDefault()
      if (this.sendingEmail) {
        this.$noty.info('Please be patient while we send the email.')
        return false
      }

      try {
        this.sendingEmail = true
        this.graderForm.course_id = this.courseId
        const { data } = await this.graderForm.post('/api/invitations/grader')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$bvModal.hide('modal-invite-grader')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          this.allFormErrors = this.graderForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-invite-graders')
          this.$nextTick(() => {
            fixInvalid()
          })
        }
      }
      this.sendingEmail = false
    }
  }
}
</script>
