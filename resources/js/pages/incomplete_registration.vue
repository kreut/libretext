<template>
  <div>
    <b-modal
      id="incomplete-registration"
      title="Incomplete Registration"
      :no-close-on-backdrop="true"
      :no-close-on-esc="true"
      :hide-header-close="true"
    >
      <p>
        It looks you've only partially completed the registration process for ADAPT. We can reset your account for you
        so that you can try again and fully complete the process.
      </p>

      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary"
                  @click="removeUserThenRegister()"
        >
          Let's try again!
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  metaInfo () {
    return {
      title: 'Incomplete Registration'
    }
  },
  mounted () {
    this.$bvModal.show('incomplete-registration')
  },
  methods: {
    async removeUserThenRegister () {
      try {
        const { data } = axios.delete('/api/user')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        t
        alert('sopme message')
        |
      } catch (error) {
        this.$noty(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
