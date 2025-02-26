<template>
  <div>
    <PageTitle title="D2L Brightspace Configuration"/>
    <b-container>
      <div v-if="!isValidCampusId">
        <b-alert show variant="info">
          This link is not valid. Please be sure that you typed it in correctly or contact us for assistance.
        </b-alert>
      </div>
      <div v-else>
        <p>
          Ensure the Manage LTI Advantage Tool Registrations and Manage LTI Advantage Tool Deployments
          permissions are set at the organization level and then complete the following steps.
        </p>
        <ol>
          <li>From the Admin Tools menu, click Manage Extensibility.</li>
          <li>From the LTI Advantage tab, click Register Tool.</li>
          <img alt="register tool"
               class="p-4"
               :src="asset('assets/img/d2l_brightspace/register_tool.png')"
               style="height:250px"
          >
          <li>
            Choose Standard Registration and copy the values below into the form:
            <ul style="padding-left:5px">
              <li>
                <span class="font-weight-bold">Name </span>
                <span id="name">ADAPT</span>
                <span class="text-info">
                <a href=""
                   aria-label="Copy name"
                   @click.prevent="doCopy('name')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
              </li>
              <li>
                <span class="font-weight-bold">Description</span>
                <span id="description">Online HW system</span>
                <span class="text-info">
                <a href=""
                   aria-label="Copy description"
                   @click.prevent="doCopy('description')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
              </li>
              <li>
                <span class="font-weight-bold">Domain </span>
                <span id="domain">https://{{ host }}</span>
                <span class="text-info">
                <a href=""
                   aria-label="Copy domain"
                   @click.prevent="doCopy('domain')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
              </li>
              <li>
                <span class="font-weight-bold">Redirect URLs </span>
                <span id="redirect-url">https://{{ host }}/api/lti/redirect-uri</span>
                <span class="text-info">
                <a href=""
                   aria-label="Copy Redirect URLs"
                   @click.prevent="doCopy('redirect-url')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
              </li>
              <li>
                <span class="font-weight-bold">OpenID Connect Login URL </span>
                <span id="openID-connect-login-url">https://{{ host }}/api/lti/oidc-initiation-url</span>
                <span class="text-info">
                <a href=""
                   aria-label="Copy OpenID Connect Login URL"
                   @click.prevent="doCopy('openID-connect-login-url')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
              </li>
              <li>
                <span class="font-weight-bold">Keyset URL</span>
                <span id="keyset-url">https://{{ host }}/api/lti/public-key/brightspace</span>
                <span class="text-info">
                <a href=""
                   aria-label="Copy Keyset URL"
                   @click.prevent="doCopy('keyset-url')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
              </li>
              <li>
                For Extensions, have the following checked:
                <img alt="register tool"
                     :src="asset('assets/img/d2l_brightspace/extensions.png')"
                     style="height:250px"
                >
              </li>
            </ul>
          </li>
          <li>
            Click Register
          </li>
          <li>From the Admin Tools menu, click on External Learning Tools</li>
          <li>Click New Deployment</li>
          <ul style="padding-left:5px">
            <li><span class="font-weight-bold">Tool*</span> ADAPT</li>
            <li><span class="font-weight-bold">Name*</span> <span id="name-2">ADAPT</span>
              <span class="text-info">
                <a href=""
                   aria-label="Copy name"
                   @click.prevent="doCopy('name-2')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span></li>
            <li><span class="font-weight-bold">Description</span> <span id="description-2">Online HW system</span>
              <span class="text-info">
                <a href=""
                   aria-label="Copy description"
                   @click.prevent="doCopy('description')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
            </li>
            <li>For Extensions have the following checked,</li>
            <img alt="register tool"
                 class="p-4"
                 :src="asset('assets/img/d2l_brightspace/extensions 2.png')"
                 style="height:250px"
            >
            <li>For Security Settings have the following checked,</li>
            <img alt="register tool"
                 class="p-4"
                 :src="asset('assets/img/d2l_brightspace/security settings.png')"

            >
            <li>For Configuration Settings have the following checked,</li>
            <img alt="register tool"
                 class="p-4"
                 :src="asset('assets/img/d2l_brightspace/configuration settings.png')"
            >
            <li>Also, as appropriate, make the tool available to organizational units as needed</li>
            <img alt="register tool"
                 class="p-4"
                 :src="asset('assets/img/d2l_brightspace/make available.png')"
                 style="height:150px"
            >
          </ul>

          <li>After the deployment is created, you should see the ADAPT tool on the  External Learning Tools page. Click on the tool, scroll to the bottom and click on View
            Links.
          </li>
          <li>Create a New Link and copy in the following values:</li>
          <ul style="padding-left:5px">
            <li>
              <span class="font-weight-bold">Name </span>
              <span id="name">ADAPT</span>
              <span class="text-info">
                <a href=""
                   aria-label="Copy name"
                   @click.prevent="doCopy('name')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
            </li>
            <li>
              <span class="font-weight-bold">URL </span>
              <span id="redirect-url">https://{{ host }}/api/lti/redirect-uri</span>
              <span class="text-info">
                <a href=""
                   aria-label="Copy Redirect URLs"
                   @click.prevent="doCopy('redirect-url')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
            </li>
            <li>
              <span class="font-weight-bold">Description</span>
              <span id="description">Online HW system</span>
              <span class="text-info">
                <a href=""
                   aria-label="Copy description"
                   @click.prevent="doCopy('description')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
            </li>
            <li> For the Type, choose Deep Linking Quick Link.</li>
          </ul>
        </ol>
        <div class="mb-5">
          <b-card header="default" header-html="LTI Registration">
            <p>
             Finally, go back to Manage Extensibility in the Admin Tools and locate the ADAPT app.  Click on it, scroll to the bottom, and then copy
              the Brightspace Registration Details so that we can register your school's information. Once
              completed, you will
              receive
              a follow-up email explaining how your instructors can
              then
              link
              up
              their ADAPT courses to Brightspace.
            </p>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              label-for="platform_id"
            >
              <template #label>
                Client Id*
              </template>
              <b-form-input
                id="developer_key_id"
                v-model="ltiRegistrationForm.client_id"
                type="text"
                required
                :class="{ 'is-invalid': ltiRegistrationForm.errors.has('client_id') }"
                @keydown="ltiRegistrationForm.errors.clear('client_id')"
              />
              <has-error :form="ltiRegistrationForm" field="client_id"/>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              label-for="brightspace_keyset_url"
            >
              <template #label>
                Brightspace Keyset URL*
              </template>
              <b-form-input
                id="brightspace_keyset_url"
                v-model="ltiRegistrationForm.brightspace_keyset_url"
                type="text"
                required
                :class="{ 'is-invalid': ltiRegistrationForm.errors.has('brightspace_keyset_url') }"
                @keydown="ltiRegistrationForm.errors.clear('brightspace_keyset_url')"
              />
              <has-error :form="ltiRegistrationForm" field="brightspace_keyset_url"/>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              label-for="brightspace_oauth2_access_token_url"
            >
              <template #label>
                Brightspace OAuth2 Access Token URL*
              </template>
              <b-form-input
                id="brightspace_oauth2_access_token_url"
                v-model="ltiRegistrationForm.brightspace_oauth2_access_token_url"
                type="text"
                required
                :class="{ 'is-invalid': ltiRegistrationForm.errors.has('brightspace_oauth2_access_token_url') }"
                @keydown="ltiRegistrationForm.errors.clear('brightspace_oauth2_access_token_url')"
              />
              <has-error :form="ltiRegistrationForm" field="brightspace_oauth2_access_token_url"/>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              label-for="openid_connect_authentication_endpoint"
            >
              <template #label>
                OpenID Connect Authentication Endpoint*
              </template>
              <b-form-input
                id="openid_connect_authentication_endpoint"
                v-model="ltiRegistrationForm.openid_connect_authentication_endpoint"
                type="text"
                required
                :class="{ 'is-invalid': ltiRegistrationForm.errors.has('openid_connect_authentication_endpoint') }"
                @keydown="ltiRegistrationForm.errors.clear('openid_connect_authentication_endpoint')"
              />
              <has-error :form="ltiRegistrationForm" field="openid_connect_authentication_endpoint"/>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              label-for="issuer"
            >
              <template #label>
                Issuer*
              </template>
              <b-form-input
                id="issuer"
                v-model="ltiRegistrationForm.issuer"
                type="text"
                required
                :class="{ 'is-invalid': ltiRegistrationForm.errors.has('issuer') }"
                @keydown="ltiRegistrationForm.errors.clear('issuer')"
              />
              <has-error :form="ltiRegistrationForm" field="issuer"/>
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
              <has-error :form="ltiRegistrationForm" field="admin_name"/>
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
              <has-error :form="ltiRegistrationForm" field="admin_email"/>
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
</template>

<script>
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Form from 'vform'
import { getSchools, searchBySchool, selectSchool, submitDetails, validateCampusId } from '../helpers/lti'

const defaultIssuerForm = {
  lms: 'd2l_brightspace',
  client_id: '',
  brightspace_keyset_url: '',
  brightspace_oauth2_access_token_url: '',
  openid_connect_authentication_endpoint: '',
  issuer: '',
  admin_name: '',
  admin_email: ''
}
export default {
  name: 'D2lBrightSpace',
  components: {
    FontAwesomeIcon,
    Autocomplete
  },
  metaInfo () {
    return { title: 'D2L Brightspace Config' }
  },
  data: () => ({
    isValidCampusId: false,
    host: window.location.hostname,
    school: '',
    campusId: '',
    copyIcon: faCopy,
    ltiRegistrationForm: new Form(defaultIssuerForm)
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
