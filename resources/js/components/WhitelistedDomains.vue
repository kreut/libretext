<template>
  <b-card header="default"
          header-html="<h2 class=&quot;h7&quot;>Whitelisted Domains</h2>"
          v-show="showWhiteListedDomains"
          class="mb-3"
  >
    <p>
      For courses not served through an LMS, whitelisted domains determine acceptable emails for students in your
      course. For example, if you just want
      to accept
      mySchool.edu
      email addresses, you can whitelist mySchool.edu.
    </p>
    <p>
      Then, only students using email addresses that contain mySchool.edu will be able to enroll.
    </p>
    <b-form-group

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
          required
          class="mr-2"
          size="sm"
        />
        <b-button variant="outline-primary" size="sm" @click="addWhitelistedDomain(whitelistedDomain)">
          Add whitelisted domain
        </b-button>
      </b-form-row>
      <div class="d-flex flex-row">
            <span v-for="chosenWhitelistedDomain in whitelistedDomains"
                  :key="`whitelisted-domain-${chosenWhitelistedDomain.id}`"
                  class="mt-2"
            >
              <b-button size="sm"
                        variant="secondary"
                        class="mr-2"
                        style="line-height:.8"
                        @click="removeWhitelistedDomain(chosenWhitelistedDomain)"
              ><span v-html="chosenWhitelistedDomain.whitelisted_domain"/> x</b-button>
            </span>
      </div>
    </b-form-group>

  </b-card>
</template>

<script>
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
  name: 'WhitelistedDomains',
  props: {
    courseId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    whitelistedDomains: [],
    whitelistedDomain: '',
    showWhiteListedDomains: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.getWhiteListedDomains()
  },
  methods: {
    async getWhiteListedDomains () {
      try {
        const { data } = await axios.get(`/api/whitelisted-domains/${this.courseId}`)

        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.showWhiteListedDomains = data.show_whitelisted_domains
          this.whitelistedDomains = data.whitelisted_domains
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async removeWhitelistedDomain (chosenWhitelistedDomain) {
      if (this.whitelistedDomains.length === 1) {
        this.$noty.error('You need at least 1 whitelisted domain.')
        return
      }

      try {
        const { data } = await axios.delete(`/api/whitelisted-domains/${chosenWhitelistedDomain.id}`)
        if (data.type === 'info') {
          await this.getWhiteListedDomains()
          this.$noty.info(`${chosenWhitelistedDomain.whitelisted_domain} has been removed.`)
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async addWhitelistedDomain (whitelistedDomain) {
      whitelistedDomain = whitelistedDomain.replace(/(.*)@/, '')
      if (whitelistedDomain) {
        if (!this.whitelistedDomains.includes(whitelistedDomain)) {
          try {
            const { data } = await axios.post(`/api/whitelisted-domains/${this.courseId}`, { whitelisted_domain: whitelistedDomain })
            this.$noty[data.type](data.message)
            if (data.type === 'success') {
              await this.getWhiteListedDomains()
            }
          } catch (error) {
            this.$noty.error(error.message)
          }
        } else {
          this.$noty.info(`${whitelistedDomain} is already on your list of whitelisted domains.`)
        }
      }
      this.whitelistedDomain = ''
    }
  }
}
</script>

