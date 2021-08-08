<template>
  <div>
    <b-card header="default" header-html="Iframe Information">
      <b-card-text>
        <p>
          If you embed Adapt in another webpage, such as a Libretext, you can control how much information is shown
          to the student. This can be updated at the question level within an assignment, or you can perform a mass
          update here.
        </p>
        <p>
          By default, the attribution (author/license), assignment information (assignment name), and submission
          information (date of submission) are not shown.
        </p>
        <p>
          <span class="font-italic font-weight-bold">Assignment Information:</span>
          <b-button size="sm" class="mr-2" variant="primary" @click="updateIframeInformation('assignment','show')">
            Show All
          </b-button>
          <b-button size="sm" variant="danger" @click="updateIframeInformation('assignment','hide')">
            Hide All
          </b-button>
        </p>
        <p>
          <span class="font-italic font-weight-bold">Submission Information:</span>
          <b-button size="sm" class="mr-2" variant="primary" @click="updateIframeInformation('submission','show')">
            Show All
          </b-button>
          <b-button size="sm" variant="danger" @click="updateIframeInformation('submission','hide')">
            Hide All
          </b-button>
        </p>
        <p>
          <span class="font-italic font-weight-bold">Attribution:</span>
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

export default {
  data: () => ({
    courseId: 0
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
  },
  methods: {
    async updateIframeInformation (item, action) {
      try {
        const { data } = await axios.patch(`/api/courses/${this.courseId}/iframe-properties`,
          {
            item: item,
            action: action
          })

        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
