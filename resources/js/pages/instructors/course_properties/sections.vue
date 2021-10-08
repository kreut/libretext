<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-sections'" />
    <b-modal
      id="modal-delete-section"
      ref="modal"
      title="Confirm Delete Section"
      ok-title="Yes, delete section!"
      :no-close-on-esc="true"
    >
      <p>By deleting the section, you will also delete:</p>
      <ol>
        <li>All assignments associated with the section</li>
        <li>All submitted student responses</li>
        <li>All student scores</li>
      </ol>
      <b-alert show variant="danger">
        <span class="font-weight-bold">Warning! You are about to remove {{ numberOfEnrolledUsers }} students from this section along with all of their submission data and scores.  This action cannot be undone.
        </span>
      </b-alert>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-delete-section')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleDeleteSection"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-section"
             ref="modal"
             :title="sectionId ? 'Edit Section Name' : 'Add Section'"
             :no-close-on-esc="true"
    >
      <RequiredText />
      <b-form-group
        label-cols-sm="5"
        label-cols-lg="4"
        label-for="section_name"
      >
        <template slot="label">
          Section Name<Asterisk/>
        </template>
        <b-form-input
          id="section_name"
          v-model="sectionForm.name"
          type="text"
          placeholder=""
          :class="{ 'is-invalid': sectionForm.errors.has('name') }"
          @keydown="sectionForm.errors.clear('name')"
        />
        <has-error :form="sectionForm" field="name" />
      </b-form-group>
      <b-form-group
        label-cols-sm="5"
        label-cols-lg="4"
        label-for="crn"
      >
        <template slot="label">
          CRN<Asterisk/>
        </template>

        <b-form-input
          id="crn"
          v-model="sectionForm.crn"
          type="text"
          placeholder=""
          :class="{ 'is-invalid': sectionForm.errors.has('crn') }"
          @keydown="sectionForm.errors.clear('crn')"
        />
        <has-error :form="sectionForm" field="crn" />
      </b-form-group>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-section')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitSectionForm()"
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
        <div v-if="!viewStudentAccessCodes">
          <b-alert :show="true" variant="info">
            <span class="font-weight-bold font-italic">You current don't have the ability to create new sections.</span>
          </b-alert>
        </div>
        <div v-else>
          <b-card header="default" header-html="Sections">
            <b-card-text>
              <div v-if="user.email !== 'commons@libretexts.org'">
                <p>
                  This course currently runs from
                  <span class="font-weight-bold">{{
                    $moment(courseStartDate, 'YYYY-MM-DD').format('MMMM DD, YYYY')
                  }}</span> to
                  <span class="font-weight-bold">{{
                    $moment(courseEndDate, 'YYYY-MM-DD').format('MMMM DD, YYYY')
                  }}</span>.
                  The access codes will only be valid within the start and end dates of
                  this course. If you need to change these dates, you can always do so
                  <a href="" @click.prevent="$router.push({name: 'course_properties.general_info'})">here</a>.
                </p>
                <b-table striped hover :fields="fields" :items="sections">
                  <template v-slot:head(crn)>
                    CRN
                    <a id="crn-tooltip"
                       href="#"
                    >
                      <b-icon class="text-muted" icon="question-circle"/>
                    </a>
                    <b-tooltip target="crn-tooltip" triggers="hover focus" delay="500">
                      The Course Reference Number is the number that identifies a specific section of a course being offered.
                    </b-tooltip>
                  </template>
                  <template v-slot:cell(access_code)="data">
                    {{ data.item.access_code ? data.item.access_code : 'None Available' }}
                  </template>
                  <template v-slot:cell(crn)="data">
                    {{ data.item.crn ? data.item.crn : 'None Provided' }}
                  </template>
                  <template v-slot:cell(actions)="data">
                    <div class="mb-0">
                      <b-tooltip :target="getTooltipTarget('edit',data.item.id)"
                                 delay="500"
                      >
                        Edit Section
                      </b-tooltip>
                      <a :id="getTooltipTarget('edit',data.item.id)"
                         href="#"
                         class="pr-1"
                         aria-label="Refresh access code"
                         @click="initEditSection(data.item)"
                      >
                        <b-icon icon="pencil" class="text-muted" />
                      </a>

                      <b-tooltip :target="getTooltipTarget('deleteSection',data.item.id)"
                                 delay="500"
                      >
                        Delete Section
                      </b-tooltip>
                      <a :id="getTooltipTarget('deleteSection',data.item.id)"
                         href="#"
                         class="pr-1"
                         aria-label="Delete section"
                         @click="confirmDeleteSection(data.item.id)"
                      >
                        <b-icon icon="trash" class="text-muted" />
                      </a>

                      <span class="text-info">
                        <b-tooltip :target="getTooltipTarget('refreshAccessCode',data.item.id)"
                                   delay="500"
                                   triggers="hover focus"
                        >

                          You can refresh the access code if you would like to render the current access code invalid.

                        </b-tooltip>
                        <a :id="getTooltipTarget('refreshAccessCode',data.item.id)"
                           href="#"
                           class="pr-1"
                           aria-label="Refresh access code"
                           @click="refreshAccessCode(data.item.id)"
                        >
                          <b-icon icon="arrow-repeat" class="text-muted" />
                        </a>
                      </span>
                    </div>
                  </template>
                </b-table>
                <b-button class="float-right" size="sm" variant="primary" @click="initAddSection">
                  Add Section
                </b-button>
              </div>
              <div v-else>
                <b-alert :show="true" variant="info">
                  <span class="font-weight-bold">You cannot invite students to courses in the Commons.</span>
                </b-alert>
              </div>
            </b-card-text>
          </b-card>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import axios from 'axios'
import Form from 'vform'
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
  data: () => ({
    allFormErrors: [],
    courseId: '',
    courseStartDate: '',
    courseEndDate: '',
    viewStudentAccessCodes: true,
    sectionForm: new Form({
      name: '',
      crn: ''
    }),
    numberOfEnrolledUsers: 0,
    sections: [],
    sectionId: false,
    isLoading: true,
    fields: [
      {
        key: 'name',
        label: 'Section',
        isRowHeader: true
      },
      'crn',
      'access_code',
      'actions'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  async mounted () {
    this.courseId = this.$route.params.courseId
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.courseId = this.$route.params.courseId
    await this.getSections(this.courseId)
    await this.canCreateStudentAccessCodes()
    this.isLoading = false
  },
  methods: {
    async canCreateStudentAccessCodes () {
      try {
        const { data } = await axios.get('/api/sections/can-create-student-access-codes')
        this.viewStudentAccessCodes = data.can_create_student_access_codes
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async confirmDeleteSection (sectionId) {
      this.sectionId = sectionId
      try {
        const { data } = await axios.get(`/api/sections/real-enrolled-users/${this.sectionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        this.numberOfEnrolledUsers = data.number_of_enrolled_users
        data.has_enrolled_users
          ? this.$bvModal.show('modal-delete-section')
          : await this.handleDeleteSection()
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async handleDeleteSection () {
      this.isLoading = true
      try {
        const { data } = await axios.delete(`/api/sections/${this.sectionId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getSections(this.courseId)
          this.$bvModal.hide('modal-delete-section')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
      this.isLoading = false
    },
    initAddSection () {
      this.sectionId = false
      this.sectionForm.crn = ''
      this.sectionForm.name = ''
      this.sectionForm.errors.clear()
      this.$bvModal.show('modal-section')
    },
    async submitSectionForm () {
      try {
        const { data } = !this.sectionId ? await this.sectionForm.post(`/api/sections/${this.courseId}`)
          : await this.sectionForm.patch(`/api/sections/${this.sectionId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getSections(this.courseId)
          this.$bvModal.hide('modal-section')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.sectionForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-sections')
        }
      }
    },
    initEditSection (section) {
      this.sectionForm.errors.clear()
      this.sectionId = section.id
      this.sectionForm.name = section.name
      this.sectionForm.crn = section.crn
      this.$bvModal.show('modal-section')
    },
    async getSections (courseId) {
      const { data } = await axios.get(`/api/sections/${courseId}`)
      if (data.type === 'error') {
        this.$noty.error(data.message)
        return false
      }
      this.sections = data.sections
      this.courseStartDate = data.course_start_date
      this.courseEndDate = data.course_end_date
    },
    async refreshAccessCode (sectionId) {
      try {
        const { data } = await axios.patch(`/api/sections/refresh-access-code/${sectionId}`)
        if (data.type === 'error') {
          this.$noty.error('We were not able to refresh your access code.')
          return false
        }
        this.$noty.success(data.message)
        await this.getSections(this.courseId)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
<style>
body, html {
  overflow: visible;

}

svg:focus, svg:active:focus {
  outline: none !important;

}
</style>
