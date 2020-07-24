export function getSrc(question){
  let src
  switch (question.technology){
    case 'h5p':
      let url = (window.location.host.includes('adapt.libretexts.org') && !window.location.host.includes('dev')) ? 'h5p.libretexts.org' : 'dev.h5p.libretexts.org'
      url = 'dev.h5p.libretexts.org'
      src = `https://${url}/wp-admin/admin-ajax.php?action=h5p_embed&id=${question.technology_id}`
      break;
    case 'webwork':
      src = `https://webwork.libretexts.org/webwork2/html2xml?answersSubmitted=0&sourceFilePath=Library/${question.technology_id}&problemSeed=1234567&courseID=anonymous&userID=anonymous&course_password=anonymous&showSummary=1&displayMode=MathJax&language=en&outputformat=libretexts`
      break;
  }
  return src
}
