<template>
<div>
  <b-modal id="modal-libre-one-tester"
           title="LibreOne Tester"
           @hidden="refreshPage"
           >
    Thank you for helping to test out our new log in process!  After this window closes, your page will be refreshed and the
    next time that you click on Log In, you will be directed to LibreOne.
    <template #modal-footer>
      <b-button
        size="sm"
        variant="primary"
        class="float-right"
        @click="refreshPage"
      >
        OK
      </b-button>
    </template>
  </b-modal>
</div>
</template>

<script>
export default {
  layout: 'blank',
  mounted() {
    this.$bvModal.show('modal-libre-one-tester')
  },
  methods: {
    refreshPage() {
      localStorage.libreOneTester = 1
      window.location.href = '/'
    }
  }
}
</script>

<style scoped>

</style>
