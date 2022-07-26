<template>
  <div id="main-content">
    <b-modal id="modal-question-owner-transfer-request"
             title="Question Owner Transfer Request"
             no-close-on-esc
             no-close-on-backdrop
             hide-header-close
    >
      {{ message }}
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="user ? $router.push({path: '/question-editor/my-questions'}): $router.push({ name: 'login' })"
        >
          {{ user ? 'My Questions' : 'Login' }}
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
  layout: 'blank',
  data: () => ({
    action: '',
    token: '',
    message: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),

  mounted () {
    this.action = this.$route.params.action
    this.token = this.$route.params.token
    this.updatePendingQuestionOwnershipTransferRequest()
    this.$bvModal.show('modal-question-owner-transfer-request')
    this.$root.$on('bv::modal::shown', () => {
      document.getElementsByClassName('modal-dialog')[0].style.top = '30%'
    })
  },
  methods: {
    async updatePendingQuestionOwnershipTransferRequest () {
      try {
        const { data } = await axios.patch(`/api/pending-question-ownership-transfer-request`, {
          action: this.action,
          token: this.token
        })
        this.message = data.message
      } catch (error) {
        this.message = error.message
      }
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
