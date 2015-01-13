
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
    semestre = json_input['semestre']

    client = create_client(json_input['server'], json_input['params'])

#    full_name = client.get_full_name()   # TODO
    is_student = client.get_som_student()
    subjects = []

    if is_student and semestre:
        studia = client.get_studia()
        for studium in studia:
            for zapisny_list in client.get_zapisne_listy(studium.key):
                moj_rok = zapisny_list.akademicky_rok
                if not any(ak_rok == moj_rok for ak_rok, sem in semestre): continue
                for predmet in client.get_hodnotenia(studium.key, zapisny_list.key)[0]:
                    moj_sem = predmet.semester
                    if not any((moj_rok, moj_sem) == (ak_rok, sem) for ak_rok, sem in semestre): continue
                    subjects.append(dict(
                        skratka=predmet.skratka,
                        nazov=predmet.nazov,
                        semester=moj_sem,
                        akRok=moj_rok,
                        rokStudia=studium.rok_studia,
                        studijnyProgram=dict(skratka=studium.sp_skratka, nazov=studium.sp_popis),
                    ))

    client.logout()

    result = {}
#    result['full_name'] = full_name
    result['is_student'] = is_student
    result['subjects'] = subjects

    print(json.dumps(result))

if __name__ == '__main__':
    main()

