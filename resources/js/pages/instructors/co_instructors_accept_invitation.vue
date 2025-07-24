<template>
  <div>
    <b-modal id="modal-accept-invitation-response"
             :title="response.type === 'success' ? 'Success' : 'Error'"
             :hide-footer="response.type === 'error'"
             :hide-header-close="response.type === 'error'"
             no-close-on-backdrop
             no-close-on-esc
    >
      {{response.message}}
      <template #modal-footer>
        <b-button
          variant="primary"
          v-if="!user"
          size="sm"
          @click="$router.push({name:'welcome'})"
        >
          Log In
        </b-button>
        <b-button
          variant="primary"
          v-if="user"
          size="sm"
          @click="$router.push({name:'home'})"
        >
          My Courses
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'

export default {
  name: 'co_instructors_accept_invitation',
  layout: 'blank',
  data: () => ({
    response: {}
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  },
  mounted () {
    const accessCode = this.$route.params.accessCode
    this.addCoInstructor(accessCode)
  },
  methods: {
    async addCoInstructor (accessCode) {
      try {
        const { data } = await axios.post(`/api/co-instructors`, { access_code: accessCode })
        this.response = data
        this.$bvModal.show('modal-accept-invitation-response')
      } catch (error) {
        this.response.type = 'error'
        this.response.message = error.message
      }
    }
  }
}
</script>

<style scoped>

</style>
