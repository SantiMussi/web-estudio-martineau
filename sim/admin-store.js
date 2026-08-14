const DB = {
    async init() {
        if (!localStorage.getItem('martineau_db')) {
            const response = await fetch('data.json');
            const data = await response.json();
            localStorage.setItem('martineau_db', JSON.stringify(data));
        }
    },

    get() {
        return JSON.parse(localStorage.getItem('martineau_db')) || { productos: [], proyectos: [] };
    },

    save(data) {
        localStorage.setItem('martineau_db', JSON.stringify(data));
    },

    add(collection, item) {
        const db = this.get();
        item.id = Date.now();
        db[collection].push(item);
        this.save(db);
        return item;
    },

    delete(collection, id) {
        const db = this.get();
        db[collection] = db[collection].filter(el => el.id !== parseInt(id));
        this.save(db);
    }
};