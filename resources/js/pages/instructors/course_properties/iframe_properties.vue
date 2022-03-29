<template>
  <div>
    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Embed information</h2>">
      <b-card-text>
        <p>
          If you embed ADAPT in another webpage, such as a Libretext, you can control how much information is shown
          to the student. Assignment, Submission, and Attribution information can be updated at the question level
          within an assignment, or you can perform a mass
          update here. You can also choose to show or hide the question numbers.
        </p>
        <p>
          By default, the question numbers, attribution (author/license), assignment information (assignment name), and
          submission
          information (date of submission) are not shown.
        </p>
        <p>
          <span class="font-weight-bold">Question numbers:</span>
          <toggle-button
            class="mt-2"
            :width="90"
            :value="questionNumbersShownInIframe"
            :sync="true"
            :font-size="14"
            :margin="4"
            :color="toggleColors"
            :labels="{checked: 'Shown', unchecked: 'Hidden'}"
            @change="updateIframeInformation('question_numbers',!questionNumbersShownInIframe ? 'show' : 'hide')"
          />
        </p>
        <p>
          <span class="font-weight-bold">Assignment Information:</span>
          <b-button size="sm" class="mr-2" variant="primary" @click="updateIframeInformation('assignment','show')">
            Show All
          </b-button>
          <b-button size="sm" variant="danger" @click="updateIframeInformation('assignment','hide')">
            Hide All
          </b-button>
        </p>
        <p>
          <span class="font-weight-bold">Submission Information:</span>
          <b-button size="sm" class="mr-2" variant="primary" @click="updateIframeInformation('submission','show')">
            Show All
          </b-button>
          <b-button size="sm" variant="danger" @click="updateIframeInformation('submission','hide')">
            Hide All
          </b-button>
        </p>
        <p>
          <span class="font-weight-bold">Attribution:</span>
          <b-button size="sm" class="mr-2" variant="primary" @click="updateIframeInformation('attribution','show')">
            Show All
          </b-button>
          <b-button size="sm" variant="danger" @click="updateIframeInformation('attribution','hide')">
            Hide All
          </b-button>
        </p>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>

import axios from 'axios'

import { ToggleButton } from 'vue-js-toggle-button'

export default {
  metaInfo () {
    return { title: 'Course Embed Properties' }
  },
  components: { ToggleButton },
  data: () => ({
    courseId: 0,
    toggleColors: window.config.toggleColors,
    questionNumbersShownInIframe: false
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getCourseInfo(this.courseId)
  },
  methods: {
    async getCourseInfo (courseId) {
      try {
        const { data } = await axios.get(`/api/courses/${courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questionNumbersShownInIframe = data.course.question_numbers_shown_in_iframe
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateIframeInformation (item, action) {
      try {
        const { data } = await axios.patch(`/api/courses/${this.courseId}/iframe-properties`,
          {
            item: item,
            action: action
          })
        this.$noty[data.type](data.message)
        if (item === 'question_numbers' && data.type !== 'error'){
          this.questionNumbersShownInIframe = !this.questionNumbersShownInIframe
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
