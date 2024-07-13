<template>
  <div>
    <PageTitle title="Blackboard Configuration"/>
    <b-container>
      <div v-if="!isValidCampusId">
        <b-alert show variant="info">
          This link is not valid. Please be sure that you typed it in correctly or contact us for assistance.
        </b-alert>
      </div>
      <div v-else>
        <div class="mb-5">
          <b-card header="default" header-html="LTI Registration">
            <p>
              Using the following configuration, you can integrate your Blackboard installation with ADAPT via LTI 1.3,
              also
              known as LTI Advantage. As shown in the video, you'll just need to enter two pieces of information:
            </p>
            <p>
              <span class="font-weight-bold">Client ID:</span>
              <span id="client-id">4f726988-88c9-4aea-8959-050ba999b8bb</span>
              <span class="text-info">
              <a href=""
                 aria-label="Copy client id"
                 @click.prevent="doCopy('client-id')"
              >
                <font-awesome-icon :icon="copyIcon"/>
              </a>
            </span>
            </p>
            <p>
              <span class="font-weight-bold">Target Link URI:</span>
              <span id="redirect-uri">https://adapt.libretexts.org/api/lti/redirect-uri</span>
              <span class="text-info">
              <a href=""
                 aria-label="Copy redirect uri"
                 @click.prevent="doCopy('redirect-uri')"
              >
                <font-awesome-icon :icon="copyIcon"/>
              </a>
            </span>
            </p>
            <p>
              In addition, please let us know your school so that we can complete the integration process on our
              end:</p>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="schools"
            >
              <template v-slot:label>
                School*
              </template>
              <autocomplete
                ref="schoolSearch"
                :search="searchBySchool"
                @submit="selectSchool"
              />
              <input type="hidden" class="form-control is-invalid">
              <div class="help-block invalid-feedback">
                {{ ltiRegistrationForm.errors.get('school') }}
              </div>
            </b-form-group>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="admin_name"
            >
              <template #label>
                Admin Name*
              </template>
              <b-form-input
                id="admin_name"
                v-model="ltiRegistrationForm.admin_name"
                type="text"
                placeholder=""
                required
                :class="{ 'is-invalid': ltiRegistrationForm.errors.has('admin_name') }"
                @keydown="ltiRegistrationForm.errors.clear('admin_name')"
              />
              <has-error :form="ltiRegistrationForm" field="admin_name"/>
            </b-form-group>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="admin_email"
            >
              <template #label>
                Admin Email*
              </template>
              <b-form-input
                id="admin_email"
                v-model="ltiRegistrationForm.admin_email"
                type="text"
                placeholder=""
                required
                :class="{ 'is-invalid': ltiRegistrationForm.errors.has('admin_email') }"
                @keydown="ltiRegistrationForm.errors.clear('admin_name')"
              />
              <has-error :form="ltiRegistrationForm" field="admin_email"/>
            </b-form-group>
            <div class="mt-3">
              <b-button variant="primary" size="sm" @click="submitDetails">
                Submit
              </b-button>
            </div>
          </b-card>
        </div>
        <hr>
        <h2>Blackboard Admin</h2>
        <div>
          <b-embed
            type="iframe"
            aspect="16by9"
            src="https://www.youtube.com/embed/td95okNF-Lk?rel=0"
            allowfullscreen
          />
          <hr>
        </div>
        <h2>Instructor</h2>
        <div>
          <b-embed
            type="iframe"
            aspect="16by9"
            src="https://www.youtube.com/embed/mQpklvhb6Vo?rel=0"
            allowfullscreen
          />
          <hr>
        </div>
        <h2>Student</h2>
        <div>
          <b-embed
            type="iframe"
            aspect="16by9"
            src="https://www.youtube.com/embed/g-6ARvD6oBQ?rel=0"
            allowfullscreen
          />
        </div>
      </div>
    </b-container>
  </div>
</template>

<script>
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'
import { getSchools, searchBySchool, selectSchool, submitDetails, validateCampusId } from '~/helpers/lti'
import Form from 'vform'

export default {
  components: {
    FontAwesomeIcon,
    Autocomplete
  },
  data: () => ({
    isValidCampusId: true,
    copyIcon: faCopy,
    ltiRegistrationForm: new Form({
      lms: 'blackboard',
      campusId: '',
      school: ''
    })
  }),
  mounted () {
    this.schools = this.getSchools()
    this.ltiRegistrationForm.school = 'fake school name'
    this.campusId = this.$route.params.campusId
    this.ltiRegistrationForm.campus_id = this.campusId
    this.validateCampusId(this.campusId)
  },
  methods: {
    doCopy,
    validateCampusId,
    submitDetails,
    getSchools,
    searchBySchool,
    selectSchool
  }

}
</script>

<style scoped>

</style>
