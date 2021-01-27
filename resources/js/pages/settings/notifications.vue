<template>
  <div>
    test
  </div>
</template>

<script>
import Form from 'vform'

export default {
  scrollToTop: false,

  data: () => ({
    hwNotificationForm: new Form({
      timeframe: ''
    })
  }),

  methods: {
    submitHWNotificationForm () {

    }
  }
}
</script>
