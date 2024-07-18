<template>
  <span class="initials-circle">
    {{ initials }}
  </span>
</template>

<script>
export default {
  name: 'UserInitials',
  props: {
    userName: {
      type: String,
      required: true
    }
  },
  computed: {
    initials() {
      // Split the username into parts and get the first letter of each part
      return this.userName.split(' ').map(name => name[0]).join('').toUpperCase()
    }
  }
}
</script>

<style scoped>
.initials-circle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px; /* Adjusted to be smaller */
  height: 30px; /* Adjusted to be smaller */
  background-color: #007bff;
  color: #fff;
  border-radius: 50%;
  font-size: 12px; /* Small font size */
  font-weight: bold;
  text-transform: uppercase;
}
</style>
