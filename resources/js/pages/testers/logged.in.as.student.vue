<template>
  <div id="main-content">
    <b-modal id="modal-logged-in-as-student"
             :title="user ? 'Successful Log In' : 'Log In Error'"
             hide-footer
             no-close-on-esc
             no-close-on-backdrop
             hide-header-close
    >
      <div v-if="user">
        You have successfully logged in as {{ user.first_name }} {{ user.last_name }}. Please navigate to
        the testing page in another tab within this browser so the student can complete the test.
      </div>
      <div v-else>
        We could not log you in as another user.
      </div>
    </b-modal>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  layout: 'blank',
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.$bvModal.show('modal-logged-in-as-student')
    this.$root.$on('bv::modal::shown', (bvEvent, modalId) => {
      document.getElementsByClassName('modal-dialog')[0].style.top = '30%'
    })
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
