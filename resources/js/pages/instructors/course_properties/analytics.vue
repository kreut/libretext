<template>
  <div>
    <div class="vld-parent">
      <b-alert show variant="info">
        Currently in Beta: URL directs to commons-staging.libretexts.org
      </b-alert>
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading && user.role === 2">
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Analytics</h2>">
          <b-card-text>
            <div v-if="authorized">
              You can view your course analytics by going to <a
                :href="`https://commons-staging.libretexts.org/analytics/${analyticsCourseId}`"
                target=" _blank"
              >Analytics Dashboard</a>.
            </div>
            <div v-if="!authorized">
              <div v-if="sharedKey">
                <p>
                  Please visit the <a href="https://commons-staging.libretexts.org/analytics">Analytics Dashboard</a> to
                  first sync your analytics with ADAPT using
                  the shared key:
                </p>
                <p id="shared-key" class="text-center">
                  {{ sharedKey }} <span class="text-info">
                    <a href=""
                       aria-label="Copy shared key"
                       @click.prevent="doCopy('shared-key')"
                    >
                      <font-awesome-icon :icon="copyIcon" />
                    </a>
                  </span>
                </p>
                <p>After syncing the two, you can re-load this page to view your course analytics.</p>
              </div>
              <div v-if="!sharedKey">
                <b-alert show variant="info">
                  Please contact support. It looks like you no longer have access to your shared key.
                </b-alert>
              </div>
            </div>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import axios from 'axios'
import { mapGetters } from 'vuex'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  name: 'Analytics',
  middleware: 'auth',
  components: {
    Loading,
    FontAwesomeIcon
  },
  metaInfo () {
    return { title: 'Course Assignment Group Weights' }
  },
  data: () => ({
    copyIcon: faCopy,
    isLoading: true,
    courseId: 0,
    sharedKey: '',
    authorized: false,
    analyticsCourseId: 0
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.doCopy = doCopy
    this.courseId = this.$route.params.courseId
    this.getCourseAnalytics()
  },
  methods: {
    async getCourseAnalytics () {
      try {
        const { data } = await axios.get(`/api/analytics-dashboard/${this.courseId}`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.sharedKey = data.shared_key
        this.authorized = data.authorized
        this.analyticsCourseId = data.analytics_course_id
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
