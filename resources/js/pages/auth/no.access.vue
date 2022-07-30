<template>
  <div id="main-content">
    <b-modal id="modal-no-access"
             title="No Access"
             no-close-on-esc
             no-close-on-backdrop
             hide-header-close
    >
      <p v-if="!user">
        You do not have access to this page since you are not logged in.
      </p>
      <p v-if="user">
        You are currently logged in as {{ user.first_name }} {{ user.last_name }} with the role of
        {{ getUserRole() }}.  With these credentials you don't have access to this page.
      </p>
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="user ? logout() : $router.push({ name: 'login' })"
        >
          {{ user ? 'Logout' : 'Login' }}
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import { logout } from '~/helpers/Logout'

export default {
  layout: 'blank',
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.logout = logout
    this.$bvModal.show('modal-no-access')
    this.$root.$on('bv::modal::shown', (bvEvent, modalId) => {
      document.getElementsByClassName('modal-dialog')[0].style.top = '30%'
    })
  },
  methods: {
    getUserRole () {
      let role
      switch (this.user.role) {
        case (2):
          role = 'Instructor'
          break
        case (3):
          role = 'Student'
          break
        case (4):
          role = 'Grader'
          break
        case (5):
          role = 'Non-instructor question editor'
          break
        default:
          role = 'Unknown'
      }
      return role
    }
  }
}
</script>
<style scoped>
.modal-dialog {
  position: absolute;
  top: 50% !important;
  left: 50% !important;
  transform: translate(-50%, -50%) !important;
}
</style>
