<template>
  <div>
    <b-modal
      id="modal-not-enrolled-in-lms"
      title="Not Enrolled in LMS"
      no-close-on-backdrop
      no-close-on-esc
      hide-footer
    >
      It looks like you've enrolled in this ADAPT course using the email <strong>{{ user.email }}</strong>. However,
      this email does not exist in your instructor's roster in your school's Learning Management System.
    </b-modal>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  name: 'not_enrolled_in_lms',
  layout: 'blank',
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.$bvModal.show('modal-not-enrolled-in-lms')
  }
}
</script>
