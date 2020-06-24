<template>
  <div>
    {{ assignments }}
  </div>
</template>

<script>
import axios from 'axios'

export default {
  middleware: 'auth',
  data: () => ({
    assignments: ''
  }),
  mounted () {
    try {
      //todo: turn this into a function and call that way
      axios.get('/api/assignments').then(
        response => this.assignments = response.data
      );
    } catch (error) {
      // todo: force error
      alert(error.response)
    }
  },
  methods: {},
  metaInfo () {
    return { title: this.$t('home') }
  }
}
</script>
