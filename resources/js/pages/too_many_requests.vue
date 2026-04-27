<template>
  <div>
    <b-modal id="modal-too-many-requests"
             title="Too Many Requests"
             hide-footer
             no-close-on-backdrop
             no-close-on-esc
             centered
             visible
    >
      You are making requests too quickly.
      <span v-if="retryAfter">Please wait {{ retryAfter }} seconds before trying again.</span>
      <span v-else>Please wait a minute before trying again.</span>
      <div class="text-right mt-3">
        <b-button variant="primary" @click="goHome">
        OK
        </b-button>
      </div>
    </b-modal>
  </div>
</template>

<script>
export default {
  layout: 'blank',

  computed: {
    retryAfter () {
      return this.$route.query.retryAfter || null
    }
  },

  methods: {
    goHome () {
      this.$router.push({ name: 'welcome' })
    }
  }
}
</script>
