<template>
  <div>
    <div v-if="hasAccess">
      <PageTitle title="Instructor Access Codes"/>
      <div class="vld-parent">
        <loading :active.sync="isLoading"
                 :can-cancel="true"
                 :is-full-page="true"
                 :width="128"
                 :height="128"
                 color="#007BFF"
                 background="#FFFFFF"
        />
        <div v-show="!isLoading">
          <AccessCodes access-code-type="instructor"/>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import AccessCodes from '~/components/AccessCodes'
import { mapGetters } from 'vuex'

export default {
  components: {
    Loading,
    AccessCodes
  },
  metaInfo () {
    return { title: this.$t('Instructor Access Codes') }
  },
  data: () => ({
    hasAccess: false,
    isLoading: true
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.hasAccess = this.isMe && (this.user !== null)
    if (!this.hasAccess) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
    setTimeout(this.setIsLoadingToFalse, 1000)
  },
  methods: {
    setIsLoadingToFalse () {
      this.isLoading = false
    }
  }
}
</script>
