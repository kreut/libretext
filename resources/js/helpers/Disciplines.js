import axios from 'axios'

export function resetDiscipline () {
  this.activeDiscipline = {}
  this.disciplineForm.name = ''
}

export async function getDisciplines (disciplineManager = false) {
  try {
    const { data } = await axios.get('/api/disciplines')
    if (data.type === 'error') {
      this.$noty.error(data.message)
      return false
    }
    this.disciplineOptions = disciplineManager ? [] : [{ value: null, text: 'Choose a discipline' }]
    for (let i = 0; i < data.disciplines.length; i++) {
      const discipline = data.disciplines[i]
      this.disciplineOptions.push({ value: discipline.id, text: discipline.name })
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}

export function initEditDiscipline (discipline) {
  this.activeDiscipline = discipline
  this.disciplineForm.name = discipline.text
  this.$bvModal.show('modal-edit-new-discipline')
}

export function initDeleteDiscipline (discipline) {
  this.activeDiscipline = discipline
  this.$bvModal.show('modal-delete-discipline')
}

export async function saveDiscipline (activeDisciplineId = null) {
  let url
  let method
  if (activeDisciplineId) {
    url = `/api/disciplines/${activeDisciplineId}`
    method = 'patch'
  } else {
    url = `/api/disciplines`
    method = 'post'
  }
  try {
    const { data } = await this.disciplineForm[method](url)
    this.$noty[data.type](data.message)
    if (data.type === 'error') {
      return false
    } else {
      if (this.$route.name === 'open-courses') {
        await this.getOpenCourses()
      }
      await this.getDisciplines(this.$route.name === 'disciplineManager')
      this.$bvModal.hide('modal-edit-new-discipline')
    }
  } catch (error) {
    if (!error.message.includes('status code 422')) {
      this.$noty.error(error.message)
    }
  }
}

export async function deleteDiscipline (discipline) {
  try {
    const { data } = await axios.delete(`/api/disciplines/${discipline}`)
    this.$noty[data.type](data.message)
    if (data.type === 'error') {
      return false
    } else {
      if (this.$route.name === 'open-courses') {
        await this.getOpenCourses()
      }
      await this.getDisciplines(this.$route.name === 'disciplineManager')
      this.disciplineCache++
      this.$bvModal.hide('modal-delete-discipline')
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}
