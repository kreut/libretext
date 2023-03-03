<template>
  <div class="vld-parent">
    <loading :active.sync="isLoading"
             :can-cancel="true"
             :is-full-page="true"
             :width="128"
             :height="128"
             color="#007BFF"
             background="#FFFFFF"
    />
    <div v-if="!isLoading">
      <b-card header-html="<h2 class=&quot;h7&quot;>Account Customizations</h2>">
        <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-account-customizations'"/>
        <b-form>
          <b-form-group
            label-cols-sm="4"
            label-cols-lg="3"
            label="Case Study Notes*"
            label-for="case_study_notes"
          >
            <b-form-radio-group
              id="case_study_notes"
              v-model="accountCustomizationsForm.case_study_notes"
              class="mt-2"
            >
              <b-form-radio value="1">
                Shown
              </b-form-radio>
              <b-form-radio value="0">
                Hidden
              </b-form-radio>
            </b-form-radio-group>
          </b-form-group>
          <div class="float-right">
            <b-button variant="primary" size="sm" @click="update">
              Update
            </b-button>
          </div>
        </b-form>
      </b-card>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import AllFormErrors from '~/components/AllFormErrors.vue'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  components: { AllFormErrors, Loading },
  data: () => ({
    isLoading: true,
    allFormErrors: [],
    accountCustomizationsForm: new Form({
      case_study_notes: 0
    })
  }),
  mounted () {
    this.getAccountCustomizations()
  },
  methods: {
    async update () {
      try {
        const { data } = await this.accountCustomizationsForm.patch('/api/account-customizations')
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          fixInvalid()
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-account-customizations')
        }
      }
    },
    async getAccountCustomizations () {
      try {
        const { data } = await axios.get('/api/account-customizations')
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.accountCustomizationsForm = new Form(data.account_customizations)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>

<style scoped>

</style>
