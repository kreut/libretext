<template>
  <div>
    <PageTitle title="View Question" />
    <div>
      <iframe v-show="non_technology"
              :id="`non-technology-iframe-1`"
              allowtransparency="true"
              frameborder="0"
              :src="non_technology_iframe_src"
              style="width: 1px;min-width: 100%;"
      />
    </div>
    <div v-html="technology_iframe" />
  </div>
</template>
<script>

import { mapGetters } from 'vuex'
import { h5pResizer } from '~/helpers/H5PResizer'
import axios from 'axios'

export default {
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    technology_iframe: '',
    non_technology: '',
    non_technology_iframe_src: ''

  }),
  async mounted () {
    if (this.user.role === 3) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
    h5pResizer()
    this.questionId = this.$route.params.questionId
    await this.getSelectedQuestions(this.questionId)
  },
  methods: {
    async getSelectedQuestions (questionId) {
      try {
        const { data } = await axios.get(`/api/questions/${questionId}`)
        console.log(data)
        this.technology_iframe = data.question.technology_iframe
        this.non_technology_iframe_src = data.question.non_technology_iframe_src
        this.non_technology = data.question.non_technology
        iFrameResize({ log: false }, `#${data.question.iframe_id}`)
        iFrameResize({ log: false }, `#non-technology-iframe-1`)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  },
  metaInfo () {
    return { title: this.$t('home') }
  }
}
</script>
