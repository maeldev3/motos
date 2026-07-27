// main_example.dart
// Exemple minimal d'utilisation du service API dans une app Flutter.

import 'package:flutter/material.dart';
import 'api_service.dart';

void main() => runApp(const MyApp());

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(home: MotosPage());
  }
}

class MotosPage extends StatefulWidget {
  const MotosPage({super.key});
  @override
  State<MotosPage> createState() => _MotosPageState();
}

class _MotosPageState extends State<MotosPage> {
  final ApiService api = ApiService();
  List<dynamic> motos = [];
  bool loading = true;
  String? erreur;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    try {
      await api.login('admin@moto-api.com', 'password123');
      final liste = await api.getMotos();
      setState(() {
        motos = liste;
        loading = false;
      });
    } catch (e) {
      setState(() {
        erreur = e.toString();
        loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Liste des motos')),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : erreur != null
              ? Center(child: Text('Erreur : $erreur'))
              : ListView.builder(
                  itemCount: motos.length,
                  itemBuilder: (context, index) {
                    final moto = motos[index];
                    return ListTile(
                      title: Text('${moto['marque']} ${moto['modele']}'),
                      subtitle: Text(moto['immatriculation']),
                      trailing: Text(moto['statut']),
                    );
                  },
                ),
    );
  }
}
