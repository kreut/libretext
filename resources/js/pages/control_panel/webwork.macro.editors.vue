<template>
  <div>
    <div class="vld-parent">
      <PageTitle title="WeBWork Macro Editors"/>
      <loading
        :active.sync="isLoading"
        :can-cancel="true"
        :is-full-page="true"
        :width="128"
        :height="128"
        color="#007BFF"
        background="#FFFFFF"
      />

      <div v-if="!isLoading">

        <!-- ── Grant editor role ─────────────────────────────────────── -->
        <b-card class="mb-4" header-html="Grant Macro Editor Role">
          <b-form-row class="align-items-end">
            <b-col cols="6" md="10" class="mb-2">
              <autocomplete
                ref="userSearch"
                class="pr-2"
                style="width:650px"
                :search="searchByUser"
                inline
                @submit="selectUser"
              />
            </b-col>
            <b-col cols="auto" class="mb-2">
              <b-button
                variant="primary"
                size="sm"
                :disabled="!selectedUser"
                @click="grantRole"
              >
                Grant Role
              </b-button>
            </b-col>
          </b-form-row>
        </b-card>

        <!-- ── Current editors ──────────────────────────────────────── -->
        <b-card header-html="Current Macro Editors">
          <p>ADAPT admins are automatically given editing rights.</p>
          <div v-if="editors.length">
            <table class="table table-striped" aria-label="WeBWork Macro Editors">
              <thead>
              <tr>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Granted By</th>
                <th scope="col">Date Granted</th>
                <th scope="col">Actions</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="editor in editors" :key="`editor-${editor.id}`">
                <td>{{ editor.name }}</td>
                <td>{{ editor.email }}</td>
                <td>{{ editor.granted_by_name }}</td>
                <td style="white-space:nowrap">{{ formatDate(editor.created_at) }}</td>
                <td>
                  <b-button
                    size="sm"
                    variant="outline-danger"
                    @click="revokeRole(editor)"
                  >
                    Revoke
                  </b-button>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
          <b-alert v-else show variant="info">
            No macro editors have been granted yet.
          </b-alert>
        </b-card>

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

export default {
  name: 'WebworkMacroEditors',
  middleware: 'auth',
  components: { Loading, Autocomplete },

  data: () => ({
    isLoading: true,
    editors: [],
    users: [],
    selectedUser: null
  }),

  async mounted () {
    await Promise.all([this.getEditors(), this.getAllUsers()])
    this.isLoading = false
  },

  methods: {
    formatDate (dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr)
      return `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(-2)}`
    },

    async getEditors () {
      try {
        const { data } = await axios.get('/api/webwork-macro-editors')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.editors = data.editors
      } catch (error) {
        this.$noty.error(error.message)
      }
    },

    async getAllUsers () {
      try {
        const { data } = await axios.get('/api/user/potential-webwork-editors')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.users = data.users
      } catch (error) {
        this.$noty.error(error.message)
      }
    },

    searchByUser (input) {
      if (input.length < 1) return []
      return this.users
        .filter(user => user.toLowerCase().includes(input.toLowerCase()))
        .sort()
    },

    selectUser (selectedUser) {
      if (selectedUser) {
        this.selectedUser = selectedUser
      }
    },

    async grantRole () {
      if (!this.selectedUser) return
      try {
        const { data } = await axios.post('/api/webwork-macro-editors', {
          user: this.selectedUser
        })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.selectedUser = null
          this.$refs.userSearch.value = ''
          await this.getEditors()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },

    async revokeRole (editor) {
      try {
        const { data } = await axios.delete(`/api/webwork-macro-editors/${editor.id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.editors = this.editors.filter(e => e.id !== editor.id)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
