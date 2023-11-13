<template>
  <div>
    <div>
      <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-api-details'"/>
      <PageTitle title="Canvas API Configuration"/>
      <div v-if="!isLoading">
        <b-container>
          <div v-if="!isValidCampusId">
            <b-alert show variant="info">
              This link is not valid. Please be sure that you typed it in correctly or contact us for assistance.
            </b-alert>
          </div>
          <div v-else>
            <div>
              <p>
                The first thing that you'll need to do is to update ADAPT's LTI 1.3 settings. Please find the LTI key,
                and
                update
                the custom fields under Additional Settings by adding:
              </p>
              <p>
                <span class="font-weight-bold">Custom Fields:</span>
                <span id="custom-fields">canvas_assignment_id=$Canvas.assignment.id</span>
                <span class="text-info"><a href=""
                                           aria-label="Copy custom fields"
                                           @click.prevent="doCopy('custom-fields')"
                >
                <font-awesome-icon :icon="copyIcon"/>
              </a></span>:
              </p>
              <p>The rest of the fields in Additional Settings may remain blank.</p>
              <p>
                <img alt="image of Canvas additional settings" style="width:800px"
                     :src="asset('assets/img/Canvas API screenshots/Custom fields.png')"
                >
              </p>
              <p>Hit Save which will bring you back to the Developer Keys page.</p>
              <hr>
              <p>When back on the Developer Keys page, create a new API key.</p>
              <p>
                <span class="font-weight-bold">Key Name:</span>
                <span id="api-key-name">{{ appName }} API key</span>
                <span class="text-info">
                <a href=""
                   aria-label="Copy key name"
                   @click.prevent="doCopy('api-key-name')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
              </p>
              <p>
                <span class="font-weight-bold">Redirect URIs:</span>
                <span id="redirect-uris">{{ origin }}/instructors/courses/lms/access-granted</span>
                <span class="text-info">
                <a href=""
                   aria-label="Copy redirect uri"
                   @click.prevent="doCopy('redirect-uris')"
                >
                  <font-awesome-icon :icon="copyIcon"/>
                </a>
              </span>
              </p>
              <p>Next, click on the Enforce Scopes toggle and allow the following scopes:</p>
              <h5>Assignment Groups</h5>
              <p>
                <img alt="image of Canvas additional settings" style="width:800px"
                     :src="asset('assets/img/Canvas API screenshots/assignment groups.jpg')"
                >
              </p>
              <h5>Assignments</h5>
              <p>
                <img alt="image of Canvas additional settings" style="width:800px"
                     :src="asset('assets/img/Canvas API screenshots/Assignments.jpg')"
                >
              </p>
              <h5>Courses</h5>
              <p>
                <img alt="image of Canvas additional settings" style="width:800px"
                     :src="asset('assets/img/Canvas API screenshots/courses.jpg')"
                >
              </p>

              <p>
                After saving the API key, go to the Developer Keys page. Please turn the key on:
              </p>
              <img alt="image of Canvas additional settings" style="width:200px"
                   :src="asset('assets/img/Canvas API screenshots/turn-key-on.png')"
              >
              <p>
                Finally, enter the following information which can be
                found in the Details column:
              </p>
              <b-form-group
                label-cols-sm="2"
                label-cols-lg="1"
                label-for="api_key_id"
                label="Client ID*"
                label-size="sm"
              >
                <b-form-input
                  id="api_key_id"
                  v-model="apiConfigForm.api_key"
                  type="text"
                  size="sm"
                  placeholder="Example. 10000000000094"
                  required
                  :class="{ 'is-invalid': apiConfigForm.errors.has('api_key') }"
                  @keydown="apiConfigForm.errors.clear('api_key')"
                />
                <has-error :form="apiConfigForm" field="api_key"/>
              </b-form-group>
              <b-form-group
                label-cols-sm="2"
                label-cols-lg="1"
                label-for="api_secret"
                label="API Secret*"
                label-size="sm"
              >
                <b-form-input
                  id="api_secret"
                  v-model="apiConfigForm.api_secret"
                  type="text"
                  size="sm"
                  placeholder="Click on Show Key.  Example. dHTW8xABd7rDrVcQTxum3A0zJOWG6pMrORtRdJOMdvZdXnt9mo"
                  required
                  :class="{ 'is-invalid': apiConfigForm.errors.has('api_secret') }"
                  @keydown="apiConfigForm.errors.clear('api_secret')"
                />
                <has-error :form="apiConfigForm" field="api_secret"/>
              </b-form-group>
            </div>
            <div>
              <b-button variant="primary" size="sm" @click="submitApiKey">
                Submit Details
              </b-button>
            </div>
          </div>
        </b-container>
      </div>
    </div>
  </div>
</template>

<script>
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors.vue'
import axios from 'axios'

export default {
  components: {
    FontAwesomeIcon,
    AllFormErrors
  },
  data: () => ({
    origin: window.location.origin,
    appName: window.config.appName,
    allFormErrors: [],
    isValidCampusId: true,
    isLoading: true,
    copyIcon: faCopy,
    apiConfigForm: new Form({
      api_key: '',
      api_secret: '',
      campus_id: ''
    })
  }),
  mounted () {
    this.isLoading = true
    this.campusId = this.$route.params.campusId
    this.apiConfigForm.campus_id = this.campusId
    this.validateCampusId(this.campusId)
  },
  methods: {
    doCopy,
    async validateCampusId (campusId) {
      try {
        const { data } = await axios.get(`/api/lti-registration/is-valid-campus-id/api-check/${campusId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.isValidCampusId = data.is_valid_campus_id
        this.apiConfigForm.campus_id = this.campusId
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async submitApiKey () {
      try {
        const { data } = await this.apiConfigForm.patch(`/api/lti-registration/api-key`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.apiConfigForm = new Form({
          api_key: '',
          api_secret: ''
        })
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.apiConfigForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-api-details')
        }
      }
    }
  }
}
</script>
