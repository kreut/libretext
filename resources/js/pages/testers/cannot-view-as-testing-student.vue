<template>
  <div id="main-content">
    <b-modal id="modal-cannot-view"
             title="Cannot View"
             no-close-on-esc
             no-close-on-backdrop
             hide-header-close
    >
      You cannot view this page since you are logged in as a testing student.
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          variant="primary"
          @click="logout()"
        >
          OK
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
    this.$bvModal.show('modal-cannot-view')
    this.$root.$on('bv::modal::shown', (bvEvent, modalId) => {
      document.getElementsByClassName('modal-dialog')[0].style.top = '30%'
    })
    this.logout = logout
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
