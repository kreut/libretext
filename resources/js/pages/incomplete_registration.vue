<template>
  <div>
    <b-modal
      id="incomplete-registration"
      title="Incomplete Registration"
      :no-close-on-backdrop="true"
      :no-close-on-esc="true"
      :hide-header-close="true"
    >
      <p>
        It looks you've only partially completed the registration process for ADAPT. We can reset your account for you
        so that you can try again and fully complete the process.
      </p>
      <b-form-row>
        <b-form-select id="assignment"
                       v-model="registerAs"
                       :options="registerAsOptions"
                       style="width:240px"
        />
      </b-form-row>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary"
                  @click="removeUserThenRegister()"
        >
          Let's try again!
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  metaInfo () {
    return {
      title: 'Incomplete Registration'
    }
  },
  data: () => ({
    registerAs: null,
    registerAsOptions: [
      { value: null, text: 'Please choose an option' },
      { value: 'student', text: 'Student' },
      { value: 'instructor', text: 'Instructor' },
      { value: 'grader', text: 'Grader' },
      { value: 'non-instructor-question-editor', text: 'Non-Instructor Editor' }
    ]
  }),
  mounted () {
    this.$bvModal.show('incomplete-registration')
  },
  methods: {
    async removeUserThenRegister () {
      if (this.registerAs === null) {
        this.$noty.info('You need to let us know which registration type.')
        return false
      }
      try {
        const { data } = await axios.delete('/api/user')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        window.location.href = `/register/${this.registerAs}`
      } catch (error) {
        this.$noty(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
