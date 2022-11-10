<template>
  <div>
    <div v-if="hasAccess">
      <b-modal id="modal-confirm-update"
               title="Confirm update role"
      >
        <div v-if="!activeUser.role">
          <b-alert show variant="info">
            Please first select a role for {{ activeUser.first_name }} {{ activeUser.last_name }}.
          </b-alert>
        </div>
        <p v-if="activeUser.role">
          You are about to give {{ activeUser.first_name }} {{ activeUser.last_name }} the role of
          {{ activeUser.role }}.
        </p>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-confirm-update')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleUpdate()"
          >
            Do it!
          </b-button>
        </template>
      </b-modal>
      <b-modal id="modal-confirm-delete"
               title="Confirm Delete User"
      >
        <p>
          You are about to completely remove {{ activeUser.first_name }} {{ activeUser.last_name }} from ADAPT.
        </p>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-confirm-delete')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleDelete()"
          >
            Do it!
          </b-button>
        </template>
      </b-modal>
      <PageTitle title="Users With No Role"/>
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
          <b-table
            v-show="usersWithNoRole.length"
            aria-label="Users with no role"
            striped
            hover
            :no-border-collapse="true"
            :items="usersWithNoRole"
            :fields="usersWithNoRoleFields"
          >
            <template v-slot:cell(name)="data">
              {{ data.item.first_name }} {{ data.item.last_name }}
            </template>
            <template v-slot:cell(created_at)="data">
              {{ $moment(data.item.created_at).format('MM-DD-YYYY') }}
            </template>
            <template v-slot:cell(role)="data">
              <b-form-radio-group v-model="data.item.role">
                <b-form-radio value="instructor">
                  Instructor
                </b-form-radio>
                <b-form-radio value="student">
                  Student
                </b-form-radio>
              </b-form-radio-group>
            </template>
            <template v-slot:cell(actions)="data">
              <div class="d-flex inline">
                <span class="pr-2">
                  <b-button variant="primary" size="sm" @click="confirmUpdate(data.item)">
                    Update
                  </b-button>
                </span>
                <b-button variant="danger" size="sm" @click="confirmDelete(data.item)">
                  Delete
                </b-button>
              </div>
            </template>
          </b-table>
          <div v-show="!usersWithNoRole.length">
            <b-alert show>
              All users currently have roles
            </b-alert>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import axios from 'axios'
import { mapGetters } from 'vuex'

export default {
  components: {
    Loading
  },
  data: () => ({
    hasAccess: false,
    isLoading: true,
    activeUser: {},
    usersWithNoRole: [],
    usersWithNoRoleFields: [
      'email',
      'name',
      {
        key: 'created_at',
        label: 'Registration'
      },
      'role',
      'actions'
    ]
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.hasAccess = this.isMe && (this.user !== null)
    if (!this.hasAccess) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.getUsersWithNoRole()
  },
  methods: {
    confirmUpdate (user) {
      this.activeUser = user
      this.$bvModal.show('modal-confirm-update')
    },
    confirmDelete (user) {
      this.activeUser = user
      this.$bvModal.show('modal-confirm-delete')
    },
    async handleDelete () {
      try {
        const { data } = await axios.delete(`/api/users-with-no-role/${this.activeUser.id}`)
        this.isLoading = false
        this.$bvModal.hide('modal-confirm-delete')
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        await this.getUsersWithNoRole()
      } catch (error) {
        this.$noty.error(error.message)
        this.isLoading = false
        this.$bvModal.hide('modal-confirm-delete')
      }
    },
    async handleUpdate () {
      try {
        const { data } = await axios.patch(`/api/users-with-no-role/${this.activeUser.id}`, { role: this.activeUser.role })
        this.isLoading = false
        this.$bvModal.hide('modal-confirm-update')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        await this.getUsersWithNoRole()
      } catch (error) {
        this.$noty.error(error.message)
        this.isLoading = false
        this.$bvModal.hide('modal-confirm-update')
      }
    },
    async getUsersWithNoRole () {
      try {
        const { data } = await axios.get('/api/users-with-no-role')
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.usersWithNoRole = data.users_with_no_role
        for (let i = 0; i < this.usersWithNoRole.length; i++) {
          this.usersWithNoRole[i].role = ''
        }
      } catch (error) {
        this.$noty(error.message)
        this.isLoading = false
      }
    }
  }
}
</script>
