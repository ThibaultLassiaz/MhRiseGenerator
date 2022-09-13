function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function reset() {
    while (document.querySelector('.glyphicon-remove')) {
        document.querySelector('.glyphicon-remove').click()
        await sleep(10)
    }
}

async function fillArmors(armors) {
    await reset();
    for (const armor of armors) {
        const selects = document.querySelectorAll('select')
        armor.forEach((value, index) => {
            selects[index].value = value
            selects[index].dispatchEvent(new Event('change'))
        })
        await sleep(50)
        document.querySelector('button').click()
        await sleep(100)
    }
}

// usage :
fillArmors([
  	[
      "Kamura Legacy Head Scarf", // name
      0, // def
      0, // fire
      0, // water
      0, // thunder
      0, // ice
      0, // dragon,2],
      0, // Slot change 1
      0, // Slot change 2
      0, // Slot change 3
      'Constitution', // Skill name 1
      3, // Skill value 1
      'Botanist',  // Skill name 2
      -2 // Skill value 2
      'None' // Skill value 3
      0  // Skill value 3
      'None' // Skill value 4
      0  // Skill value 4
   	],
  	["Bone Helm X",0,0,4]],
		// ...
);