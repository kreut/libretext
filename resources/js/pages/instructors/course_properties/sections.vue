<template>
  <div>
    <b-modal
      id="modal-delete-section"
      ref="modal"
      title="Confirm Delete Section"
      ok-title="Yes, delete section!"
      @ok="handleDeleteSection"
    >
      <p>By deleting the section, you will also delete:</p>
      <ol>
        <li>All assignments associated with the section</li>
        <li>All submitted student responses</li>
        <li>All student scores</li>
      </ol>
      <b-alert show variant="danger">
        <span class="font-weight-bold">Warning! You are about to remove {{ numberOfEnrolledUsers }} students from this section along with all of their submission data and scores.  This action cannot be undone.
        </span>
      </b-alert>
    </b-modal>

    <b-modal id="modal-section"
             ref="modal"
             :title="sectionId ? 'Edit Section Name' : 'Add Section'"
             @ok="submitSectionForm"
    >
      <b-form-group
        id="section_name"
        label-cols-sm="5"
        label-cols-lg="4"
        label="Section Name"
        label-for="section name"
      >
        <b-form-input
          id="section_name"
          v-model="sectionForm.name"
          type="text"
          placeholder=""
          :class="{ 'is-invalid': sectionForm.errors.has('name') }"
          @keydown="sectionForm.errors.clear('name')"
        />
        <has-error :form="sectionForm" field="name" />
      </b-form-group>
    </b-modal>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading && user.role === 2">
        <b-card header="default" header-html="Sections">
          <b-card-text>
            <b-table striped hover :fields="fields" :items="sections">
              <template v-slot:cell(access_code)="data">
                {{ data.item.access_code ? data.item.access_code : 'None Available' }}
              </template>
              <template v-slot:cell(actions)="data">
                <div class="mb-0">
                  <span class="pr-1" @click="initEditSection(data.item.id, data.item.name)">
                    <b-tooltip :target="getTooltipTarget('edit',data.item.id)"
                               delay="500"
                    >
                      Edit Section
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('edit',data.item.id)" icon="pencil" />
                  </span>

                  <span class="pr-1" @click="confirmDeleteSection(data.item.id)">
                    <b-tooltip :target="getTooltipTarget('deleteSection',data.item.id)"
                               delay="500"
                    >
                      Delete Section
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('deleteSection',data.item.id)" icon="trash" />
                  </span>
                  <span class="text-info">
                    <b-tooltip :target="getTooltipTarget('refreshAccessCode',data.item.id)"
                               delay="500"
                    >

                      You can refresh the access code if you would like to render the current access code invalid.

                    </b-tooltip>
                    <b-icon-arrow-repeat :id="getTooltipTarget('refreshAccessCode',data.item.id)"
                                         variant="dark"
                                         @click="refreshAccessCode(data.item.id)"
                    />
                  </span>
                </div>
              </template>
            </b-table>
            <b-button class="float-right" variant="primary" @click="initAddSection">
              Add Section
            </b-button>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>
<script>
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({
    sectionForm: new Form({
      name: ''
    }),
    numberOfEnrolledUsers: 0,
    sections: [],
    sectionId: false,
    isLoading: true,
    fields: [
      {
        key: 'name',
        label: 'Section'
      },
      'access_code',
      'actions'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  async mounted () {
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.courseId = this.$route.params.courseId
    await this.getSections(this.courseId)

    this.isLoading = false
  },
  methods: {
    async confirmDeleteSection (sectionId) {
      this.sectionId = sectionId
      try {
        const { data } = await axios.get(`/api/sections/real-enrolled-users/${this.sectionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        this.numberOfEnrolledUsers = data.number_of_enrolled_users
        data.hasEnrolledUsers
          ? this.$bvModal.show('modal-delete-section')
          : await this.handleDeleteSection()
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async handleDeleteSection () {
      try {
        const { data } = await axios.delete(`/api/sections/${this.sectionId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getSections(this.courseId)
          this.$bvModal.hide('modal-delete-section')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    initAddSection () {
      this.sectionId = false
      this.sectionForm.name = ''
      this.sectionForm.errors.clear()
      this.$bvModal.show('modal-section')
    },
    async submitSectionForm (bvEvt) {
      bvEvt.preventDefault()
      try {
        const { data } = !this.sectionId ? await this.sectionForm.post(`/api/sections/${this.courseId}`)
          : await this.sectionForm.patch(`/api/sections/${this.sectionId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getSections(this.courseId)
          this.$bvModal.hide('modal-section')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    initEditSection (sectionId, sectionName) {
      this.sectionForm.errors.clear()
      this.sectionId = sectionId
      this.sectionForm.name = sectionName
      this.$bvModal.show('modal-section')
    },
    async getSections (courseId) {
      const { data } = await axios.get(`/api/sections/${courseId}`)
      if (data.type === 'error') {
        this.$noty.error(data.message)
        return false
      }
      this.sections = data.sections
    },
    async refreshAccessCode (sectionId) {
      try {
        const { data } = await axios.patch(`/api/sections/refresh-access-code/${sectionId}`)
        if (data.type === 'error') {
          this.$noty.error('We were not able to refresh your access code.')
          return false
        }
        this.$noty.success(data.message)
        await this.getSections(this.courseId)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
<style>
body, html {
  overflow: visible;

}

svg:focus, svg:active:focus {
  outline: none !important;

}
</style>
