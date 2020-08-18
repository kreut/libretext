<template>
  <div>
   I'm hjere
  </div>
</template>

<script>
import axios from 'axios'
import Form from "vform"


export default {
  middleware: 'auth',
  data: () => ({}),
  mounted() {
    this.assignmentId = this.$route.params.assignmentId
   alert(this.assignmentId)
  },
  methods: {

  }
}
</script>
