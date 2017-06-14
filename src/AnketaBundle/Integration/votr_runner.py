
import os
import sys
import json
from os.path import join, dirname, abspath

anketa_root = join(dirname(abspath(__file__)), '../../..')
votr_root = join(anketa_root, 'vendor/svt/votr')

sys.path.insert(0, votr_root)

# --------------------------------------------------

def main():
    from fladgejt.login import create_client

    json_input = json.load(sys.stdin)
    fakulta = json_input['fakulta']
    semestre = json_input['semestre']
    expand_subjects = json_input['expand_subjects']
    relevantne_roky = [ak_rok for ak_rok, sem in semestre]

    client = create_client(json_input['server'], json_input['params'])

    # TODO: full_name = client.get_full_name()
    is_student = False
    subjects = []

    if client.get_som_student():
        studia = client.get_studia()
        for studium in studia:
            if (studium.sp_skratka == 'ZEkP' and
                not studium.organizacna_jednotka):
                studium = studium._replace(organizacna_jednotka='PriF')
            # TODO: pouzivat zapisny_list.organizacna_jednotka,
            # ked bude v REST API
            if fakulta and studium.organizacna_jednotka != fakulta:
                continue

            for zapisny_list in client.get_zapisne_listy(studium.studium_key):
                if zapisny_list.akademicky_rok not in relevantne_roky:
                    continue
                is_student = True
                zapisny_list_key = zapisny_list.zapisny_list_key
                for predmet in client.get_hodnotenia(zapisny_list_key)[0]:
                    subj_time_id = [zapisny_list.akademicky_rok,
                                    predmet.semester]
                    if subj_time_id not in semestre:
                        continue

                    # Ak je to jeden z predmetov, ktore su zadefinovane v
                    # config_local.yml, ktore treba rozvinut do viacerych
                    # predmetov ako napriklad chirurgia
                    if predmet.skratka in expand_subjects:
                        expanded_predmet = expand_subjects[predmet.skratka]
                        for expanded in expanded_predmet:
                            subjects.append(dict(
                                skratka=expanded['skratka'],
                                nazov=expanded['nazov'],
                                semester=predmet.semester,
                                akRok=zapisny_list.akademicky_rok,
                                rokStudia=zapisny_list.rocnik,
                                studijnyProgram=dict(skratka=studium.sp_skratka,
                                                     nazov=studium.sp_popis),
                                expanded_from=dict(skratka=predmet.skratka,
                                                   nazov=predmet.nazov)
                            ))
                    else:
                        subjects.append(dict(
                            skratka=predmet.skratka,
                            nazov=predmet.nazov,
                            semester=predmet.semester,
                            akRok=zapisny_list.akademicky_rok,
                            rokStudia=zapisny_list.rocnik,
                            studijnyProgram=dict(skratka=studium.sp_skratka,
                                                 nazov=studium.sp_popis),
                            expanded_from=None
                        ))

    client.logout()

    result = {}
    # result['full_name'] = full_name
    result['is_student'] = is_student
    result['subjects'] = subjects

    print(json.dumps(result))

if __name__ == '__main__':
    main()

