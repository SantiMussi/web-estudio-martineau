// Servicio para simular la BD / API
const DB = {
    // Carga inicial desde JSON si LocalStorage está vacío
    async init() {
        if (!localStorage.getItem('martineau_db')) {
            const response = await fetch('data.json');
            const data = await response.json();
            localStorage.setItem('martineau_db', JSON.stringify(data));
        }
    },

    // Obtener todos los datos
    get() {
        return JSON.parse(localStorage.getItem('martineau_db')) || { productos: [], proyectos: [] };
    },

    // Guardar cambios
    save(data) {
        localStorage.setItem('martineau_db', JSON.stringify(data));
    },

    // Agregar un producto o proyecto
    add(collection, item) {
        const db = this.get();
        item.id = Date.now(); // Genera un ID único temporal
        db[collection].push(item);
        this.save(db);
        return item;
    },

    // Eliminar un elemento por ID
    delete(collection, id) {
        const db = this.get();
        db[collection] = db[collection].filter(el => el.id !== parseInt(id));
        this.save(db);
    }
};