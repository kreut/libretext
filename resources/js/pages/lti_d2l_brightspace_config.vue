<template>
  <div>
    <PageTitle title="D2L Brightspace Configuration"/>
    <b-container>
      <p>
        First, ensure the Manage LTI Advantage Tool Registrations and Manage LTI Advantage Tool Deployments
        permissions are set at the organization level, and complete the following steps.
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
              <span class="font-weight-bold">Name*</span>
              <span id="name">MyEssayFeedback</span>
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
              <span id="description">Formative feedback for student essays</span>
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
              <span class="font-weight-bold">Domain*</span>
              <span id="domain">https://myessayfeedback.ai</span>
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
              <span class="font-weight-bold">Redirect URLs*</span>
              <span id="redirect-url">https://myessayfeedback.ai/api/lti/redirect-uri</span>
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
              <span class="font-weight-bold">OpenID Connect Login URL*</span>
              <span id="openID-connect-login-url">https://myessayfeedback.ai/api/lti/oidc-login-request</span>
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
              <span id="keyset-url">https://myessayfeedback.ai/api/lti/public-jwk</span>
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
      </ol>
      <div class="mb-5">
        <b-card header="default" header-html="LTI Registration">
          <p>
            Next copy the Brightspace Registration Details so that we can register your school's information. Once
            completed, you will
            receive
            a follow-up email explaining how you can add MyEssayFeedback as an external app and how your instructors can
            then
            link
            up
            their MyEssayFeedback courses to D2L Brightspace.
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
              v-model="issuerForm.client_id"
              type="text"
              required
              :class="{ 'is-invalid': issuerForm.errors.has('client_id') }"
              @keydown="issuerForm.errors.clear('client_id')"
            />
            <has-error :form="issuerForm" field="client_id"/>
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
              v-model="issuerForm.brightspace_keyset_url"
              type="text"
              required
              :class="{ 'is-invalid': issuerForm.errors.has('brightspace_keyset_url') }"
              @keydown="issuerForm.errors.clear('brightspace_keyset_url')"
            />
            <has-error :form="issuerForm" field="brightspace_keyset_url"/>
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
              id="developer_key_id"
              v-model="issuerForm.brightspace_oauth2_access_token_url"
              type="text"
              required
              :class="{ 'is-invalid': issuerForm.errors.has('brightspace_oauth2_access_token_url') }"
              @keydown="issuerForm.errors.clear('brightspace_oauth2_access_token_url')"
            />
            <has-error :form="issuerForm" field="brightspace_oauth2_access_token_url"/>
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
              id="developer_key_id"
              v-model="issuerForm.openid_connect_authentication_endpoint"
              type="text"
              required
              :class="{ 'is-invalid': issuerForm.errors.has('openid_connect_authentication_endpoint') }"
              @keydown="issuerForm.errors.clear('openid_connect_authentication_endpoint')"
            />
            <has-error :form="issuerForm" field="openid_connect_authentication_endpoint"/>
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
              id="developer_key_id"
              v-model="issuerForm.issuer"
              type="text"
              required
              :class="{ 'is-invalid': issuerForm.errors.has('issuer') }"
              @keydown="issuerForm.errors.clear('issuer')"
            />
            <has-error :form="issuerForm" field="issuer"/>
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
              v-model="issuerForm.admin_name"
              type="text"
              placeholder=""
              required
              :class="{ 'is-invalid': issuerForm.errors.has('admin_name') }"
              @keydown="issuerForm.errors.clear('admin_name')"
            />
            <has-error :form="issuerForm" field="admin_name"/>
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
              v-model="issuerForm.admin_email"
              type="text"
              placeholder=""
              required
              :class="{ 'is-invalid': issuerForm.errors.has('admin_email') }"
              @keydown="issuerForm.errors.clear('admin_name')"
            />
            <has-error :form="issuerForm" field="admin_email"/>
          </b-form-group>
          <div class="float-right mt-3">
            <b-button variant="primary" size="sm" @click="submitDetails">
              Submit Details
            </b-button>
          </div>
        </b-card>
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
    copyIcon: faCopy,
    issuerForm: new Form(defaultIssuerForm)
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
