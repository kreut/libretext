<template>
  <div>
    <div v-if="hasAccess">
      <b-modal id="modal-show-message"
               title="Success!"
               hide-footer
               @hidden="reloadPage"
      >
        <b-alert variant="success" show>
          <div v-html="message" />
        </b-alert>
      </b-modal>
      <PageTitle title="Update User Info" />
      <div class="vld-parent">
        <loading :active.sync="isLoading"
                 :can-cancel="true"
                 :is-full-page="true"
                 :width="128"
                 :height="128"
                 color="#007BFF"
                 background="#FFFFFF"
        />
        <div v-show="!isLoading">
          <b-container>
            <h6>To change a user's role or email, please first select a user:</h6>
            <b-row class="mb-2">
              <div v-if="autoComplete">
                <autocomplete
                  ref="userSearch"
                  class="pr-2"
                  style="width:650px"
                  :search="searchByUser"
                  inline
                  @submit="selectUser"
                />
              </div>
            </b-row>
            <hr>
            <b-form-group
              v-if="form.user"
              label-cols-sm="2"
              label-cols-lg="1"
              label="Action:"
            >
              <b-form-radio-group
                v-model="userAction"
                stacked
                required
                @change="updateViewBasedOnAction($event)"
              >
                <b-form-radio name="userAction" value="change-role">
                  Change role
                </b-form-radio>
                <b-form-radio name="userAction" value="update-email">
                  Update email
                </b-form-radio>
              </b-form-radio-group>
            </b-form-group>
          </b-container>
          <div v-if="selectedUserInfo.role">
            <div v-if="userAction === 'change-role'">
              <div class="mb-2">
                Current role: {{
                  userRoleOptions.find(item => item.value === selectedUserInfo.role).text
                }}
              </div>
              <div>
                <b-form-group
                  label-for="role"
                  label-cols-sm="2"
                  label-cols-lg="1"
                  label="Role"
                >
                  <div>
                    <b-form-select v-model="newUserRole"
                                   :options="userRoleOptions"
                                   style="width:200px"
                                   size="sm"
                                   @change="userRoleError = ''"
                    /> <span class="ml-2">
                      <b-button size="sm" variant="primary" @click="updateRole">Save</b-button>
                    </span>
                  </div>

                  <div>
                    <ErrorMessage :message="userRoleError" />
                  </div>
                </b-form-group>
              </div>
            </div>
            <div v-if="userAction === 'update-email'">
              <b-form-group
                label-cols-sm="2"
                label-cols-lg="1"
                label-for="input-email"
                label="Email"
              >
                <b-form-row>
                  <b-form-input
                    id="input-email"
                    v-model="form.email"
                    class="mb-2"
                    type="email"
                    :class="{ 'is-invalid': form.errors.has('email')}"
                    required
                    placeholder="Enter email"
                  />
                  <span class="ml-2"><b-button size="sm" variant="primary" @click="updateEmail">Save</b-button></span>
                </b-form-row>
                <ErrorMessage :message="form.errors.get('email')" />
              </b-form-group>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'
import axios from 'axios'
import { mapGetters } from 'vuex'
import ErrorMessage from '~/components/ErrorMessage.vue'
import Form from 'vform'

const defaultUserRoleOptions = [
  { text: 'Please choose a role', value: null },
  { text: 'Instructor', value: 2 },
  { text: 'Student', value: 3 },
  { text: 'TA', value: 4 },
  { text: 'Non-instructor editor', value: 5 }
]
export default {
  components: {
    Loading,
    Autocomplete,
    ErrorMessage
  },
  data: () => ({
    message: '',
    autoComplete: true,
    autoCompleteKey: 0,
    userRoleError: '',
    newUserRole: null,
    userAction: null,
    hasAccess: false,
    isLoading: true,
    form: new Form({
      user: ''
    }),
    userRoleOptions: defaultUserRoleOptions,
    selectedUserInfo: {}
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isAdmin: () => window.config.isAdmin
  },
  mounted () {
    this.hasAccess = this.isAdmin && (this.user !== null)
    if (!this.hasAccess) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.getAllUsers()
  },
  methods: {
    reloadPage () {
      location.reload()
    },
    async updateRole () {
      if (!this.newUserRole) {
        this.userRoleError = 'Please choose a role.'
        return false
      }
      try {
        const { data } = await axios.patch('/api/user/role', {
          user_id: this.selectedUserInfo.id,
          role: this.newUserRole
        })
        if (data.type === 'success') {
          this.message = data.message
          this.$bvModal.show('modal-show-message')
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateEmail () {
      try {
        this.form.user_id = this.selectedUserInfo.id
        const { data } = await this.form.patch('/api/user/email')
        if (data.type === 'success') {
          this.message = data.message
          this.$bvModal.show('modal-show-message')
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async updateViewBasedOnAction (action) {
      this.userRoleOptions = defaultUserRoleOptions
      this.newUserRole = null
      try {
        const { data } = await axios.patch('/api/user/get-user-info-by-email', { user: this.form.user })
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.selectedUserInfo = data.user
          this.userRoleOptions.find(item => item.value === this.selectedUserInfo.role).disabled = true
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    selectUser (selectedUser) {
      if (selectedUser) {
        this.form.user = selectedUser
      }
    },
    searchByUser (input) {
      if (input.includes('https://')) {
        return [input]
      }
      if (input.length < 1) {
        return []
      }
      let matches = this.users.filter(user => user.toLowerCase().includes(input.toLowerCase()))
      let items = []
      if (matches) {
        for (let i = 0; i < matches.length; i++) {
          items.push(matches[i])
        }
        items.sort()
      }
      return items
    },
    async getAllUsers () {
      try {
        const { data } = await axios.get(`/api/user/all`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        } else {
          this.users = data.users
          this.hasAccess = true
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.isLoading = false
      }
    }
  }
}
</script>
