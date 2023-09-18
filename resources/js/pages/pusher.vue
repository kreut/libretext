<template>
  <div>
    wefwefewf
    {{ messages }}
  </div>
</template>

<script>
import { initPusher } from '~/helpers/Pusher'
export default {
  name: 'Pusher',
  data: () => ({
    messages: [],
    environment: window.config.environment
  }),
  mounted () {
    const pusher = this.initPusher()
    const channel = pusher.subscribe('my-channel')
    channel.bind('App\\Events\\StatusLiked', this.statusLiked)
    console.log(channel)
  },
  methods: {
    initPusher,
    statusLiked (data) {
      this.messages.push(data)
    }
  }
}
</script>

<style scoped>

</style>
