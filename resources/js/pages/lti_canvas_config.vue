<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-lti-details'"/>
    <PageTitle title="Canvas Configuration"/>
    <div v-if="!isLoading">
      <b-container>
        <div v-if="!isValidCampusId">
          <b-alert show variant="info">
            This link is not valid. Please be sure that you typed it in correctly or contact us for assistance.
          </b-alert>
        </div>
        <div v-else>
          <p>
            Using the following configuration, you can integrate your Canvas installation with ADAPT via LTI 1.3, also
            known as LTI Advantage. Optionally, you can following along with <a
            href="https://youtu.be/o9tNGoorUgQ" target="_blank"
          >this video</a>, which implements the steps below.
          </p>

          <p>
            In your Canvas installation, first go to the Developer Keys page and add an LTI key. When the configuration
            page
            opens up choose "Manual
            Entry" as the method to enter the configuration details.
          </p>
          <p>
            <span class="font-weight-bold">Redirect URIs:</span>
            <span id="redirect-uri">https://adapt.libretexts.org/api/lti/redirect-uri/{{ campusId }}</span>
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
            <span class="font-weight-bold">Title:</span> <span id="title">ADAPT</span> <span class="text-info">
        <a href=""
           aria-label="Copy title"
           @click.prevent="doCopy('title')"
        >
          <font-awesome-icon :icon="copyIcon"/>
          </a>
        </span>
          </p>
          <p>
            <span class="font-weight-bold">Description</span> <span id="description">Online homework platform</span>
            <span class="text-info">
          <a href=""
             aria-label="Copy description"
             @click.prevent="doCopy('description')"
          >
          <font-awesome-icon :icon="copyIcon"/>
          </a>
        </span>
          </p>
          <p>
            <span class="font-weight-bold">Target Link URI:</span>
            <span id="target-link-uri">https://adapt.libretexts.org/api/lti/redirect-uri/{{ campusId }}</span>
            <span class="text-info">
          <a href=""
             aria-label="Copy target link uri"
             @click.prevent="doCopy('target-link-uri')"
          >
          <font-awesome-icon :icon="copyIcon"/>
            </a>
        </span>
          </p>
          <p>
            <span class="font-weight-bold">OpenID Connect Initiation Url:</span>
            <span id="open-id-connect-url">https://adapt.libretexts.org/api/lti/oidc-initiation-url</span>
            <span class="text-info">
          <a href=""
             aria-label="Copy OpenID connect url"
             @click.prevent="doCopy('open-id-connect-url')"
          >
          <font-awesome-icon :icon="copyIcon"/>
            </a>
        </span>
          </p>
          <p>
            <span class="font-weight-bold">Public JWK:</span>
            <vue-json-pretty id="public-jwk" :data="publicJWK"/>
            <span class="text-info">
          <a href=""
             aria-label="Copy public JWK"
             @click.prevent="doCopy('public-jwk')"
          >
          <font-awesome-icon :icon="copyIcon"/>
            </a>
        </span>
          </p>

          <p><span class="font-weight-bold">Under LTI Advantage services toggle the following to On:</span></p>
          <ul>
            <li>Can create and view assignment data in the gradebook associated with the tool.</li>
            <li> Can view assignment data in the gradebook associated with the tool.</li>
            <li>Can view submission data for assignments associated with the tool.</li>
            <li> Can create and update submission results for assignments associated with the tool.</li>
            <li> Can retrieve user data associated with the context the tool is installed in.</li>
          </ul>
          <p>
            <span class="font-weight-bold">Open up Additional Settings and switch Privacy Level to Public:</span>
          </p>
          <p><img alt="image of Canvas additional settings" style="width:800px"
                  :src="asset('assets/img/additional_settings_canvas.png')"
          >
          </p>
          <p><span class="font-weight-bold">Placements:</span> Assignment Selection </p>
          <p>
            <span class="font-weight-bold">Target Link URI:</span>
            <span id="placement-target-link-uri">https://adapt.libretexts.org/api/lti/configure/{{ campusId }}</span>
            <span class="text-info">
          <a href=""
             aria-label="Copy placement target link uri"
             @click.prevent="doCopy('placement-target-link-uri')"
          >
          <font-awesome-icon :icon="copyIcon"/>
          </a>
        </span>
          </p>
          <p><span class="font-weight-bold">Select Message Type:</span> LtiDeepLinkingRequest</p>
          <p><span class="font-weight-bold">Final steps:</span></p>
          <ol>
            <li>Save the configuration information.</li>
            <li>From the Home Developer Key screen, turn the key to On.</li>
            <li>Copy the Developer Key ID and fill out the form below.</li>
          </ol>
          <RequiredText/>
          <div style="width:700px" class="mb-5">
            <b-card header="default" header-html="LTI Registration">
              <p>
                Please fill out the form so that we can register your school's information. Once completed, you will
                receive
                a follow-up email explaining how you can add ADAPT as an external app and how your instructors can then
                link
                up
                their ADAPT courses to Canvas.
              </p>
              <LTIRegistration :form="ltiRegistrationForm" :show-campus-id="false"/>
              <div class="float-right">
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
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors'
import LTIRegistration from '~/components/LTIRegistration'
import VueJsonPretty from 'vue-json-pretty'
import 'vue-json-pretty/lib/styles.css'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import axios from 'axios'

export default {
  components: {
    VueJsonPretty,
    FontAwesomeIcon,
    AllFormErrors,
    LTIRegistration
  },
  metaInfo () {
    return { title: 'LTI Canvas Config' }
  },
  data: () => ({
    publicJWK: {
      'd': 'tkdUVHX4yVKzkK1pPLKO11QXzteTcBF4QJIVGJ6ZjwBf7WeBIXzMrGli2XFSFum2yygrbkQlTF_Xr3yG5JC1NBK4aj4t0AE3Fy_89a_PmwFKa4aTQIPX73zP2bpFw0YHnejDzTAtdZ7HhKfB1FOKBzcF1ci-hb5rLax8mKBJ5IyIjJN-DtjBYwGr6CCYTNIJKF1Z8UT-TDYtZxj1YSvk32cka4ttMdUYdwrCKt-j1MsQiAlpA-437SxqlXUAX7ooutNCz-b-57h8_Sw7AnmO8USbtHi3Q5O__bpG_H7quv_t1WDGAoWFr6cOA2h_Kgx8WX1szMmiOPPZmpdu5YYHcQ',
      'e': 'AQAB',
      'n': '8osiSa75nmqmakwNNocLA2N2huWM9At_tjSZOFX1r4-PDclSzxhMw-ZcgHH-E_05Ec6Vcfd75i8Z-Bxu4ctbYk2FNIvRMN5UgWqxZ5Pf70n8UFxjGqdwhUA7_n5KOFoUd9F6wLKa6Oh3OzE6v9-O3y6qL40XhZxNrJjCqxSEkLkOK3xJ0J2npuZ59kipDEDZkRTWz3al09wQ0nvAgCc96DGH-jCgy0msA0OZQ9SmDE9CCMbDT86ogLugPFCvo5g5zqBBX9Ak3czsuLS6Ni9Wco8ZSxoaCIsPXK0RJpt6Jvbjclqb4imsobifxy5LsAV0l_weNWmU2DpzJsLgeK6VVw',
      'p': '-TEfpa5kz8Y6jCPJK6u5GMBXIniy1972X_HwyuqcUDZDyy2orr3rRj0sOtJoDHtC62_NnrhuvZYyW-cZ0nDzrzPj8ma-gCpbcgdRfOpEAeA6T_xjfN5KN3u3dHQ7e_qoBtCPJFhiB8Axmjs_NdbwUo0axqQB50QpbRv3gdid0qk',
      'q': '-SuCu0BGnaed3VYa7GBAyNf74eNPSn3Ht9MwK1-9iFmC5T0CULHndUcV4Zzp-qwORSYEW_R2oyfDRM_MRCosSUEiHztMZLglJeZxtBx6MjH6vLaQwW7Ixkg-69kKct8H93tC7YNTqZ14gEwT_wBfmQGqfV6R12KgRJ1KQeSSJ_8',
      'dp': 'aPCeAjjZ7YHuP_wGCOUNUvYU-8hWkIAtwyPxIpMAdusTS6oTwlrqjK7QRIk9FhyGhv2TWwcSY7avyHIfNrcoeBzjHr7T9MdhsTiRwYgqUZvrEqoX_4rhOFJaZKlaL5DUV-JWlZi-18LBYNEYgoTcufcAUqzYvFrBE1jWt5DQjdk',
      'dq': 'E7OrDJ9SdhjQ1LWAP3yE4tyhIAVXOa6kYhai0mspk2RwgyvFyReoE4_hXQuJPLbqEfGlmpfD4ba9K-26WxFymwA5cHrB2Zzt4wdLqlAuIVXuW4mb_I-D9Jm1z_RDbT3RZXIropglv12iL5LUae9fn7uP_YXCxmMYBRTi0D8Ah4U',
      'qi': 'YwLEhy55SQucj2vQqSO1dqn2YiB2ARHBA83QKb1PHflkTNGn3mR_gLow-xU7BmTmA2-9CeDHiJrD181gb48XbI24Nn4QXAjS-mYYIpFASR739UI4W5wyyOCMyFtT6OupEgkqKw_swITU1GHKYI-lB_-y0Q-XSdLmuP6ZkkdAQao',
      'alg': 'RS256',
      'kid': '58f36e10-c1c1-4df0-af8b-85c857d1634f',
      'kty': 'RSA',
      'use': 'sig'
    },
    isLoading: true,
    allFormErrors: [],
    campusId: '',
    copyIcon: faCopy,
    ltiRegistrationForm: new Form({
      admin_name: '',
      admin_email: '',
      url: '',
      vanity_url: '',
      developer_key_id: '',
      campus_id: ''
    }),
    isValidCampusId: true
  }),
  mounted () {
    this.isLoading = true
    this.doCopy = doCopy
    this.campusId = this.$route.params.campusId
    this.ltiRegistrationForm.campus_id = this.campusId
    this.validateCampusId(this.campusId)
  },
  methods: {
    async validateCampusId (campusId) {
      try {
        const { data } = await axios.get(`/api/lti-registration/is-valid-campus-id/${campusId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
        }
        this.isValidCampusId = data.is_valid_campus_id
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async submitDetails () {
      try {
        this.ltiRegistrationForm.errors.clear()
        if (this.ltiRegistrationForm.url && this.ltiRegistrationForm.url.search(/^http[s]?\:\/\//) === -1) {
          this.ltiRegistrationForm.url = 'https://' + this.ltiRegistrationForm.url
        }
        const { data } = await this.ltiRegistrationForm.post('/api/lti-registration/email-details')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.ltiRegistrationForm = new Form({ campus_id: this.campusId })
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          fixInvalid()
          this.allFormErrors = this.ltiRegistrationForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-lti-details')
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
