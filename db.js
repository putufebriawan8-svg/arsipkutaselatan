/**
 * db.js - SIPARSIP Mock Database & Persistence Engine
 * Uses localStorage to persist state across page reloads.
 * Designed to work smoothly over file:// protocol.
 */

(function () {
  const DB_KEY_USERS = 'siparsip_users';
  const DB_KEY_FILES = 'siparsip_files';
  const DB_KEY_TASKS = 'siparsip_tasks';
  const DB_KEY_ACTIVITIES = 'siparsip_activities';
  const DB_KEY_CLUSTERS = 'siparsip_clusters';
  const DB_KEY_STORAGE = 'siparsip_storage';
  const DB_KEY_SEEDED = 'siparsip_seeded';

  const defaultUsers = [
    { id: 'u1', name: 'Admin Utama', email: 'admin.siparsip@puskesmas.go.id', role: 'Super Administrator', cluster: 'All Clusters', status: 'Active', avatar: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAR8CpJdN1SqgQ_7TIrk-A6i1WG2qRtyZ71tIfLT9MMwYIklxOI6RnAJCGDqsAk1zMAX8XeQeH_idUUoxiYQwYmyFVlI3365MNP5i8wP8pCCeb3axH6B0iI55XTDAPLLjH_rh2VbM6nJMQWFktH5KVz8rU1JjmmtvuVTOQIp0VkMjP4lBfnMD6S0AFIewuJ9qj9HcopDw1Cj7OMzBY9UnPHueSdwPXazw3Ff5ll2GWpyqBVAML7sYxCWdZ1IXej_OT8sArPJ9wrtU8' },
    { id: 'u2', name: 'Budi Darmawan', email: 'budi.d@puskesmas.go.id', role: 'Manager', cluster: 'Medical Records', status: 'Active', avatar: '' },
    { id: 'u3', name: 'Siti Lutfiah', email: 'siti.l@puskesmas.go.id', role: 'Arsiparis', cluster: 'Medical Records', status: 'Active', avatar: '' },
    { id: 'u4', name: 'Sarah Jen', email: 'sarah.j@puskesmas.go.id', role: 'Arsiparis', cluster: 'HR & Finance', status: 'Active', avatar: '' },
    { id: 'u5', name: 'Dr. Aris', email: 'aris.m@puskesmas.go.id', role: 'Anggota', cluster: 'Medical Records', status: 'Active', avatar: '' },
    { id: 'u6', name: 'Agus Subagyo', email: 'agus.s@puskesmas.go.id', role: 'Anggota', cluster: 'Logistics', status: 'Active', avatar: '' },
    { id: 'u7', name: 'Dewi Lestari', email: 'dewi.l@puskesmas.go.id', role: 'Manager', cluster: 'HR & Finance', status: 'Active', avatar: '' },
    { id: 'u8', name: 'Eko Prasetyo', email: 'eko.p@puskesmas.go.id', role: 'Arsiparis', cluster: 'Logistics', status: 'Inactive', avatar: '' },
    { id: 'u9', name: 'putufebriawan', email: 'putufebriawan@puskesmas.go.id', role: 'Super Administrator', cluster: 'All Clusters', status: 'Active', avatar: '' }
  ];

  const defaultClusters = [
    { id: 'c1', name: 'Medical Records', description: 'Rekam medis pasien Puskesmas Terpadu', activeUsers: 12, size: '640 GB' },
    { id: 'c2', name: 'HR & Finance', description: 'Data kepegawaian dan laporan keuangan', activeUsers: 8, size: '380 GB' },
    { id: 'c3', name: 'Logistics', description: 'Inventarisasi obat-obatan dan alkes', activeUsers: 5, size: '180 GB' },
    { id: 'c4', name: 'General & Support', description: 'Surat masuk/keluar umum puskesmas', activeUsers: 3, size: '42 GB' }
  ];

  const defaultFiles = [
    // Active Files
    { id: 'f1', name: 'patient_report_v2.pdf', type: 'pdf', size: 1048576 * 4.5, cluster: 'Medical Records', classification: 'Terbatas', uploadDate: '2026-05-26T19:21:00Z', uploader: 'Sarah Jen', deleted: false },
    { id: 'f2', name: 'laporan_keuangan_q1_2026.xlsx', type: 'xlsx', size: 1048576 * 12.8, cluster: 'HR & Finance', classification: 'Sangat Rahasia', uploadDate: '2026-05-25T10:15:00Z', uploader: 'Dewi Lestari', deleted: false },
    { id: 'f3', name: 'inventaris_alkes_2026.docx', type: 'docx', size: 1048576 * 2.1, cluster: 'Logistics', classification: 'Biasa', uploadDate: '2026-05-24T08:30:00Z', uploader: 'Agus Subagyo', deleted: false },
    { id: 'f4', name: 'vaksinasi_booster_agenda.pdf', type: 'pdf', size: 1048576 * 1.8, cluster: 'Medical Records', classification: 'Biasa', uploadDate: '2026-05-22T14:45:00Z', uploader: 'Siti Lutfiah', deleted: false },
    { id: 'f5', name: 'sertifikat_akreditasi_puskesmas.png', type: 'png', size: 1048576 * 6.2, cluster: 'General & Support', classification: 'Biasa', uploadDate: '2026-05-20T11:00:00Z', uploader: 'Admin Utama', deleted: false },
    { id: 'f6', name: 'data_pegawai_tetap_2026.xlsx', type: 'xlsx', size: 1048576 * 3.4, cluster: 'HR & Finance', classification: 'Sangat Rahasia', uploadDate: '2026-05-18T16:20:00Z', uploader: 'Sarah Jen', deleted: false },
    { id: 'f7', name: 'sop_pendaftaran_pasien_v1.pdf', type: 'pdf', size: 1048576 * 5.1, cluster: 'General & Support', classification: 'Biasa', uploadDate: '2026-05-15T09:00:00Z', uploader: 'Budi Darmawan', deleted: false },
    // Deleted (Trash) Files
    { id: 'f8', name: 'rekap_absensi_maret_old.xlsx', type: 'xlsx', size: 1048576 * 8.2, cluster: 'HR & Finance', classification: 'Biasa', uploadDate: '2026-04-10T11:00:00Z', uploader: 'Sarah Jen', deleted: true, deletedAt: '2026-05-26T15:30:00Z' },
    { id: 'f9', name: 'draft_desain_brosur_kesling.png', type: 'png', size: 1048576 * 15.4, cluster: 'General & Support', classification: 'Biasa', uploadDate: '2026-05-01T10:00:00Z', uploader: 'Siti Lutfiah', deleted: true, deletedAt: '2026-05-26T16:00:00Z' },
    { id: 'f10', name: 'temp_data_rujukan.zip', type: 'zip', size: 1048576 * 120.5, cluster: 'Medical Records', classification: 'Terbatas', uploadDate: '2026-05-12T13:40:00Z', uploader: 'Dr. Aris', deleted: true, deletedAt: '2026-05-26T18:10:00Z' }
  ];

  const defaultTasks = [
    { id: 't1', title: 'Backup Audit Keamanan', description: 'Melakukan backup periodik seluruh sistem penyimpanan terenkripsi.', assignedTo: 'Admin Utama', priority: 'Tinggi', status: 'done', dueDate: '2026-05-26' },
    { id: 't2', title: 'Penyusunan Rekam Medis Q2', description: 'Pengelompokan berkas rekam medis pasien periode April - Juni.', assignedTo: 'Siti Lutfiah', priority: 'Sedang', status: 'in_progress', dueDate: '2026-05-29' },
    { id: 't3', title: 'Verifikasi Laporan Keuangan', description: 'Audit berkas kuitansi pengeluaran operasional ambulans.', assignedTo: 'Budi Darmawan', priority: 'Tinggi', status: 'todo', dueDate: '2026-05-30' },
    { id: 't4', title: 'Restrukturisasi Kluster Alkes', description: 'Memindahkan file inventarisasi 2025 ke folder arsip pasif.', assignedTo: 'Agus Subagyo', priority: 'Rendah', status: 'todo', dueDate: '2026-06-05' }
  ];

  const defaultActivities = [
    { id: 'a1', user: 'Sarah Jen', message: 'mengunggah file <strong>patient_report_v2.pdf</strong>', time: '2 minutes ago', category: 'Archives' },
    { id: 'a2', user: 'System', message: 'Tugas <strong>Backup Audit</strong> diselesaikan oleh System', time: '14 minutes ago', category: 'Security' },
    { id: 'a3', user: 'Firewall', message: 'Akses ditolak untuk <strong>Cluster-B7</strong> (Autentikasi gagal)', time: '1 hour ago', category: 'Firewall' },
    { id: 'a4', user: 'Admin', message: 'Pengguna baru <strong>Dr. Aris</strong> telah terdaftar', time: '3 hours ago', category: 'Admin' }
  ];

  const defaultStorage = {
    used: 1.2, // TB
    total: 5.0, // TB
    trashSize: 4.2 // GB
  };

  // Seeding Database
  function seed() {
    if (!localStorage.getItem(DB_KEY_SEEDED)) {
      localStorage.setItem(DB_KEY_USERS, JSON.stringify(defaultUsers));
      localStorage.setItem(DB_KEY_CLUSTERS, JSON.stringify(defaultClusters));
      localStorage.setItem(DB_KEY_FILES, JSON.stringify(defaultFiles));
      localStorage.setItem(DB_KEY_TASKS, JSON.stringify(defaultTasks));
      localStorage.setItem(DB_KEY_ACTIVITIES, JSON.stringify(defaultActivities));
      localStorage.setItem(DB_KEY_STORAGE, JSON.stringify(defaultStorage));
      localStorage.setItem(DB_KEY_SEEDED, 'true');
    }
  }

  // Generic DB operations
  function getData(key) {
    seed();
    return JSON.parse(localStorage.getItem(key)) || [];
  }

  function setData(key, data) {
    localStorage.setItem(key, JSON.stringify(data));
  }

  window.SIPARSIP_DB = {
    seed,
    
    // USERS CRUD
    getUsers: function () {
      return getData(DB_KEY_USERS);
    },
    addUser: function (user) {
      const users = this.getUsers();
      user.id = 'u' + (Date.now());
      users.push(user);
      setData(DB_KEY_USERS, users);
      this.addActivity('Admin', `telah menambahkan pengguna baru <strong>${user.name}</strong>`, 'Admin');
      return user;
    },
    updateUser: function (updatedUser) {
      const users = this.getUsers();
      const index = users.findIndex(u => u.id === updatedUser.id);
      if (index !== -1) {
        users[index] = { ...users[index], ...updatedUser };
        setData(DB_KEY_USERS, users);
        this.addActivity('Admin', `memperbarui profil pengguna <strong>${updatedUser.name}</strong>`, 'Admin');
        return true;
      }
      return false;
    },
    deleteUser: function (id) {
      const users = this.getUsers();
      const user = users.find(u => u.id === id);
      const filtered = users.filter(u => u.id !== id);
      setData(DB_KEY_USERS, filtered);
      if (user) {
        this.addActivity('Admin', `menghapus pengguna <strong>${user.name}</strong> dari sistem`, 'Admin');
      }
    },

    // CLUSTERS CRUD
    getClusters: function () {
      return getData(DB_KEY_CLUSTERS);
    },
    addCluster: function (cluster) {
      const clusters = this.getClusters();
      cluster.id = 'c' + (Date.now());
      cluster.activeUsers = 0;
      cluster.size = '0 GB';
      clusters.push(cluster);
      setData(DB_KEY_CLUSTERS, clusters);
      this.addActivity('Admin', `membuat kluster arsip baru <strong>${cluster.name}</strong>`, 'System');
      return cluster;
    },

    // FILES CRUD
    getFiles: function () {
      return getData(DB_KEY_FILES).filter(f => !f.deleted);
    },
    getTrashFiles: function () {
      return getData(DB_KEY_FILES).filter(f => f.deleted);
    },
    addFile: function (file) {
      const files = getData(DB_KEY_FILES);
      file.id = 'f' + (Date.now());
      file.deleted = false;
      file.uploadDate = new Date().toISOString();
      files.push(file);
      setData(DB_KEY_FILES, files);
      
      // Update storage used size
      this.updateStorageSize(file.size / 1024 / 1024 / 1024 / 1024); // convert byte to TB
      this.addActivity(file.uploader, `mengunggah file <strong>${file.name}</strong>`, 'Archives');
      return file;
    },
    deleteFile: function (id, uploaderName) {
      const files = getData(DB_KEY_FILES);
      const file = files.find(f => f.id === id);
      if (file) {
        file.deleted = true;
        file.deletedAt = new Date().toISOString();
        setData(DB_KEY_FILES, files);
        
        // Add to trash size (convert byte to GB)
        const sizeGB = file.size / (1024 * 1024 * 1024);
        const storage = JSON.parse(localStorage.getItem(DB_KEY_STORAGE)) || defaultStorage;
        storage.trashSize = parseFloat((storage.trashSize + sizeGB).toFixed(2));
        // Deduct from used space
        storage.used = Math.max(0.1, parseFloat((storage.used - (file.size / (1024*1024*1024*1024))).toFixed(4)));
        setData(DB_KEY_STORAGE, storage);

        this.addActivity(uploaderName || 'User', `memindahkan file <strong>${file.name}</strong> ke tempat sampah`, 'Archives');
        return true;
      }
      return false;
    },
    emptyTrash: function () {
      const files = getData(DB_KEY_FILES);
      const activeFiles = files.filter(f => !f.deleted);
      setData(DB_KEY_FILES, activeFiles);
      
      const storage = JSON.parse(localStorage.getItem(DB_KEY_STORAGE)) || defaultStorage;
      const reclaimed = storage.trashSize;
      storage.trashSize = 0.0;
      setData(DB_KEY_STORAGE, storage);
      
      this.addActivity('System', `mengosongkan tempat sampah, berhasil memulihkan <strong>${reclaimed} GB</strong> ruang penyimpanan.`, 'Security');
      return reclaimed;
    },

    // TASKS CRUD
    getTasks: function () {
      return getData(DB_KEY_TASKS);
    },
    addTask: function (task) {
      const tasks = this.getTasks();
      task.id = 't' + (Date.now());
      tasks.push(task);
      setData(DB_KEY_TASKS, tasks);
      this.addActivity('Admin', `membuat tugas baru: <strong>${task.title}</strong>`, 'Admin');
      return task;
    },
    updateTaskStatus: function (id, status) {
      const tasks = this.getTasks();
      const task = tasks.find(t => t.id === id);
      if (task) {
        const oldStatus = task.status;
        task.status = status;
        setData(DB_KEY_TASKS, tasks);
        
        let statusText = status === 'done' ? 'selesai' : (status === 'in_progress' ? 'sedang dikerjakan' : 'antrean');
        this.addActivity(task.assignedTo || 'User', `mengubah status tugas <strong>${task.title}</strong> menjadi <strong>${statusText}</strong>`, 'System');
        return true;
      }
      return false;
    },
    deleteTask: function (id) {
      const tasks = this.getTasks();
      const filtered = tasks.filter(t => t.id !== id);
      setData(DB_KEY_TASKS, filtered);
    },

    // ACTIVITIES
    getActivities: function () {
      return getData(DB_KEY_ACTIVITIES);
    },
    addActivity: function (user, message, category) {
      const activities = getData(DB_KEY_ACTIVITIES);
      const newAct = {
        id: 'a' + Date.now(),
        user,
        message,
        time: 'Just now',
        category: category || 'System'
      };
      activities.unshift(newAct);
      if (activities.length > 50) activities.pop(); // Keep last 50
      setData(DB_KEY_ACTIVITIES, activities);
      
      // Dispatch custom event for real-time reactivity
      window.dispatchEvent(new CustomEvent('siparsip_activity', { detail: newAct }));
    },

    // STORAGE & SYSTEM METRICS
    getStorage: function () {
      seed();
      return JSON.parse(localStorage.getItem(DB_KEY_STORAGE)) || defaultStorage;
    },
    updateStorageSize: function (addTB) {
      const storage = this.getStorage();
      storage.used = parseFloat((storage.used + addTB).toFixed(3));
      setData(DB_KEY_STORAGE, storage);
    },
    
    // HELPERS
    formatBytes: function (bytes, decimals = 2) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const dm = decimals < 0 ? 0 : decimals;
      const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
  };
})();
