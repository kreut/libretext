<template>
  <div>
    {{ assignments }}
  </div>
</template>

<script>

export default {
  middleware: 'auth',
  data: () => ({
    assignments: 'My assignments'
  }),
  mounted () {
    alert('start by doing a get to students/home which will get all assignments for the first course')
  },
  metaInfo () {
    return { title: this.$t('home') }
  }
}
</script>
