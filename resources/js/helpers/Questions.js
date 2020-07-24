export function getSrc(question){
  let src
  switch (question.technology){
    case 'h5p':
      src = `https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=${question.technology_id}`
      break;
    case 'webwork':
      src = `https://webwork.libretexts.org/webwork2/html2xml?answersSubmitted=0&sourceFilePath=Library/${question.technology_id}&problemSeed=1234567&courseID=anonymous&userID=anonymous&course_password=anonymous&showSummary=1&displayMode=MathJax&language=en&outputformat=libretexts`
      break;
  }
  return src
}
