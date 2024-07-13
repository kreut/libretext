<template>
  <div>
    <div v-if="!isLoading">
      <PageTitle title="Moodle Configuration" />
      <b-container>
        <div v-if="!isValidCampusId">
          <b-alert show variant="info">
            This link is not valid. Please be sure that you typed it in correctly or contact us for assistance.
          </b-alert>
        </div>
        <div v-else>
          <p>
            In your Moodle installation, go to Site administration->Plugins->External Tools->Manage Tools. In the "Add
            tool" box, click on
            the link: "configure a tool manually".
          </p>
          <h4 class="text-info">
            Tool Settings
          </h4>
          <p>
            <span class="font-weight-bold">Tool name:</span>
            <span id="tool-name">{{ appName }}</span>
            <span class="text-info">
              <a href=""
                 aria-label="Copy tool name"
                 @click.prevent="doCopy('tool-name')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
            </span>
          </p>
          <p>
            <span class="font-weight-bold">Tool URL:</span>
            <span id="tool-url">{{ origin }}</span>
            <span class="text-info">
              <a href=""
                 aria-label="Copy tool URL"
                 @click.prevent="doCopy('tool-url')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
            </span>
          </p>
          <p>
            <span class="font-weight-bold">Tool description:</span>
            <span id="tool-description">Online homework system</span>
            <span class="text-info">
              <a href=""
                 aria-label="Copy tool description"
                 @click.prevent="doCopy('tool-description')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
            </span>
          </p>
          <p>
            <span class="font-weight-bold">LTI version:</span>
            LTI 1.3
          </p>
          <p>
            <span class="font-weight-bold">Public key type:</span>
            RSA key
          </p>
          <p>
            <span class="font-weight-bold">Public key:</span>
            <span id="public-key">-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwfornR/zx9ou4WA4ExKu
D8z/xsfp4iNrbT1Yi1mDOIPJX5K4oY46Dl0ZkDPO9EhcncSx+YdJGx1iLYJ1+KTm
tN8eaa1k/Y6OvO8ZtodzKKOzu3lhwfmOP1UHlcSePKkNLeTmHDBbq20AvFMRpuaI
RDxWzMlTJHZD9NplPoIsDfiftiHGw1a3zNON0IQVF129MkcdutdhoDHo2QjhUsPR
YEVYkGZW1J2hmXMdLbNdTmXVClY+xhbwQY14pIfeQG3Jx+tQxI98cTBswWePQcxM
snm1z/u36485sPaucLUJfouNLUfzuDnB0eM2ffW+vPaTVYHZXF+Hmn4FTJUYdrjd
ywIDAQAB
-----END PUBLIC KEY-----

            </span>
            <span class="text-info">
              <a href=""
                 aria-label="Copy public key"
                 @click.prevent="doCopy('public-key')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
            </span> ***Important note: for public keys to be formatted properly, there needs to be a newline after
            -----BEGIN PUBLIC KEY----- in addition to a newline before -----END PUBLIC KEY-----.  After pasting the key
            into Moodle, hit return as shown in the video here.
          </p>
          <p>
            <span class="font-weight-bold">Initiate login URL:</span>
            <span id="initiate-login-url">{{ origin }}/api/lti/oidc-initiation-url</span>
            <span class="text-info">
              <a href=""
                 aria-label="Copy initiate login URL"
                 @click.prevent="doCopy('initiate-login-url')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
            </span>
          </p>
          <p>
            <span class="font-weight-bold">Redirection URI(s):</span>
            <span id="redirection-uri">{{ origin }}/api/lti/redirect-uri</span>
            <span class="text-info">
              <a href=""
                 aria-label="Copy redirection URI"
                 @click.prevent="doCopy('redirection-uri')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
            </span>
          </p>
          <p>
            <span class="font-weight-bold">Tool Configuration usage:</span>
            Show as preconfigured tool when adding an external tool
          </p>
          <p>
            <span class="font-weight-bold">Default launch container:</span>
            New window
          </p>
          <p>
            <b-form-checkbox
              id="checkbox-1"
              v-model="deepLinking"
              name="checkbox-1"
              value="checked"
              unchecked-value="not_checked"
            >
              Please check: Supports Deep Linking (Content-Item Message)
            </b-form-checkbox>
          </p>
          <h4 class="text-info">
            Services
          </h4>
          <p>
            <span class="font-weight-bold">IMS LTI Assignment and Grade Services:</span>
            Use this service for grade sync only
          </p>
          <p>
            <span class="font-weight-bold">IMS LTI Names and Role Provisioning:</span>
            Use this service to retrieve members' information as per privacy settings
          </p>
          <p>
            <span class="font-weight-bold">Tool settings:</span>
            Do not use this service
          </p>
          <h4 class="text-info">
            Privacy
          </h4>
          <p>
            <span class="font-weight-bold">Share launcher's name with tool:</span>
            Always
          </p>
          <p>
            <span class="font-weight-bold">Share launcher's email with tool:</span>
            Always
          </p>
          <p>
            <span class="font-weight-bold">Accept grades from the tool:</span>
            Always
          </p>
          <p>
            You do not need to check Force SSL: MyEssayFeedback always uses SSL.
          </p>
          <p>
            After saving the information, you should see the tool in your Moodle account: <img alt="Moodle tool"
                                                                                               :src="asset('assets/img/moodle_tool.png')"
                                                                                               height="200x"
            >
          </p>

          <p>
            Click on the list icon (<span class="text-primary"><b-icon-list-ul /></span>)
            and your "Tool configuration details" will appear in a pop-up. Please copy that information below.
          </p>
          <div style="width:700px" class="mb-5">
            <b-card header="default" header-html="LTI Registration">
              <p>
                Please fill out the form so that we can register your school's information. Once completed, we will
                review
                your request and after the
                request has been approved, you will
                receive
                a follow-up email explaining how you can add {{ appName }} as an external app and how your instructors
                can
                then
                link
                up
                their MyEssayFeedback courses to Moodle.
              </p>
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
                label-for="platform_id"
              >
                <template #label>
                  Platform ID*
                </template>
                <b-form-input
                  id="platform_id"
                  v-model="ltiRegistrationForm.platform_id"
                  type="text"
                  required
                  :class="{ 'is-invalid': ltiRegistrationForm.errors.has('platform_id') }"
                  @keydown="ltiRegistrationForm.errors.clear('platform_id')"
                />
                <has-error :form="ltiRegistrationForm" field="platform_id" />
              </b-form-group>
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
                label-for="client_id"
              >
                <template #label>
                  Client ID*
                </template>
                <b-form-input
                  id="client_id"
                  v-model="ltiRegistrationForm.client_id"
                  type="text"
                  required
                  :class="{ 'is-invalid': ltiRegistrationForm.errors.has('client_id') }"
                  @keydown="ltiRegistrationForm.errors.clear('client_id')"
                />
                <has-error :form="ltiRegistrationForm" field="client_id" />
              </b-form-group>
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
                label-for="public_keyset_url"
              >
                <template #label>
                  Public keyset URL*
                </template>
                <b-form-input
                  id="public_keyset_url"
                  v-model="ltiRegistrationForm.public_keyset_url"
                  type="text"
                  required
                  :class="{ 'is-invalid': ltiRegistrationForm.errors.has('public_keyset_url') }"
                  @keydown="ltiRegistrationForm.errors.clear('public_keyset_url')"
                />
                <has-error :form="ltiRegistrationForm" field="public_keyset_url" />
              </b-form-group>
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
                label-for="access_token_url"
              >
                <template #label>
                  Access token URL*
                </template>
                <b-form-input
                  id="access_token_url"
                  v-model="ltiRegistrationForm.access_token_url"
                  type="text"
                  required
                  :class="{ 'is-invalid': ltiRegistrationForm.errors.has('access_token_url') }"
                  @keydown="ltiRegistrationForm.errors.clear('access_token_url')"
                />
                <has-error :form="ltiRegistrationForm" field="access_token_url" />
              </b-form-group>
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
                label-for="authentication_request_url"
              >
                <template #label>
                  Authentication request URL*
                </template>
                <b-form-input
                  id="authentication_request_url"
                  v-model="ltiRegistrationForm.authentication_request_url"
                  type="text"
                  required
                  :class="{ 'is-invalid': ltiRegistrationForm.errors.has('authentication_request_url') }"
                  @keydown="ltiRegistrationForm.errors.clear('authentication_request_url')"
                />
                <has-error :form="ltiRegistrationForm" field="authentication_request_url" />
              </b-form-group>
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
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
                label-cols-sm="4"
                label-cols-lg="3"
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
                <has-error :form="ltiRegistrationForm" field="admin_name" />
              </b-form-group>
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
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
                <has-error :form="ltiRegistrationForm" field="admin_email" />
              </b-form-group>

              <div class="float-right mt-3">
                <b-button variant="primary" size="sm" @click="submitDetails">
                  Submit Details
                </b-button>
              </div>
            </b-card>
          </div>
        </div>
      </b-container>
    </div>
  </div>
</template>

<script>
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Form from 'vform'
import 'vue-json-pretty/lib/styles.css'
import { getSchools, searchBySchool, selectSchool, submitDetails, validateCampusId } from '~/helpers/lti'

export default {
  components: {
    FontAwesomeIcon,
    Autocomplete
  },
  metaInfo () {
    return { title: 'LTI Moodle Config' }
  },
  data: () => ({
    isLoading: true,
    isValidCampusId: true,
    deepLinking: 'checked',
    origin: window.location.origin,
    appName: window.config.appName,
    copyIcon: faCopy,
    ltiRegistrationForm: new Form({
      lms: 'moodle',
      admin_name: '',
      admin_email: ''
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
